<?php

namespace App\Http\Controllers\Mail;

use App\Http\Controllers\Controller;
use App\Mail\TaskAssignNotification;
use App\Models\employee;
use App\Models\Task;
use App\Models\TasksAssignee;
use App\Models\TasksChecklists;
use App\Models\TasksResult;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TasksMailController extends Controller
{
    /**
     * Master switch for the task mailing pipeline.
     *
     * When true, emails are sent immediately upon task assignment.
     * When false, only notification infrastructure is prepared (no email sent).
     */
    public const MAIL_SENDING_ENABLED = true;

    /** Notification type "code" shared by every seeded task notification type. */
    private const NOTIFICATION_CODE = 'Tasks';

    private const TYPE_TASK_CREATE = 'task_create';

    private const TYPE_TASK_UPDATE = 'task_update';

    private const TYPE_NEW_CHECKLIST = 'new_checklists_create';

    private const TYPE_TASK_COMPLETE = 'tasks_complete';

    private const TYPE_UPDATED_PERFORMS = 'updated_performs';

    /** Values that already exist in the notification_deliveries enum. */
    private const DELIVERY_CHANNEL_MAIL = 'mail';

    private const DELIVERY_STATUS_PENDING = 'pending';

    private const DELIVERY_STATUS_SUCCESS = 'success';

    private const DELIVERY_STATUS_FAILED = 'failed';

    /** Values that already exist in the notification_recipients enum. */
    private const RECIPIENT_STATUS_PENDING = 'pending';

    /**
     * Application level guard so a double submitted request cannot create two
     * identical notifications for the same task, type and recipient.
     */
    private const DUPLICATE_WINDOW_SECONDS = 30;

    /** @var array<int, employee|null> Per request employee lookup cache. */
    private array $employeeDirectory = [];

    /**
     * GET /planner/tasks/mail
     *
     * Status endpoint for the task mailing pipeline. It reports the pipeline
     * state so the front-end can verify the wiring without inspecting source code.
     */
    public function sendMail()
    {
        return response()->json([
            'success' => true,
            'message' => self::MAIL_SENDING_ENABLED
                ? 'Task mailing pipeline is active. Emails sent on assignment.'
                : 'Task mailing pipeline is prepared only. No email is sent or queued.',
            'data' => [
                'sending_enabled' => self::MAIL_SENDING_ENABLED,
                'notification_types' => $this->notificationTypeNames(),
                'delivery_channel' => self::DELIVERY_CHANNEL_MAIL,
                'task_assignment_mailable' => TaskAssignNotification::class,
                'task_assignment_view' => TaskAssignNotification::VIEW,
                'task_assignment_delivery_enabled' => TaskAssignNotification::DELIVERY_ENABLED,
                'system_mail_from' => Config::get('mail.from.address'),
            ],
        ]);
    }

    /**
     * POST /planner/tasks/notifications/task-create
     */
    public function notifyTaskCreate(Request $request)
    {
        return $this->dispatchTaskNotification(
            $request,
            self::TYPE_TASK_CREATE,
            fn(Task $task, int $actorEid, array $validated) => $this->buildTaskCreate($task, $actorEid, $validated)
        );
    }

    /**
     * POST /planner/tasks/notifications/task-update
     */
    public function notifyTaskUpdate(Request $request)
    {
        return $this->dispatchTaskNotification(
            $request,
            self::TYPE_TASK_UPDATE,
            fn(Task $task, int $actorEid, array $validated) => $this->buildTaskUpdate($task, $actorEid, $validated)
        );
    }

    /**
     * POST /planner/tasks/notifications/checklist-create
     */
    public function notifyNewChecklist(Request $request)
    {
        return $this->dispatchTaskNotification(
            $request,
            self::TYPE_NEW_CHECKLIST,
            fn(Task $task, int $actorEid, array $validated) => $this->buildNewChecklist($task, $actorEid, $validated)
        );
    }

    /**
     * POST /planner/tasks/notifications/task-complete
     */
    public function notifyTaskComplete(Request $request)
    {
        return $this->dispatchTaskNotification(
            $request,
            self::TYPE_TASK_COMPLETE,
            fn(Task $task, int $actorEid, array $validated) => $this->buildTaskComplete($task, $actorEid, $validated)
        );
    }

    /**
     * POST /planner/tasks/notifications/updated-performs
     */
    public function notifyUpdatedPerforms(Request $request)
    {
        return $this->dispatchTaskNotification(
            $request,
            self::TYPE_UPDATED_PERFORMS,
            fn(Task $task, int $actorEid, array $validated) => $this->buildUpdatedPerforms($task, $actorEid, $validated)
        );
    }

    /**
     * POST /planner/tasks/mail/{id}/assignees
     *
     * Called automatically by the planner front-end right after the assignee
     * list has been persisted by TasksController::storeAssignees. The stored
     * assignee rows are therefore the source of truth for the recipients.
     *
     * This endpoint creates the notification and immediately sends the
     * task assignment email to the assigned employees using their
     * employees.Official_Email addresses. The system sender is
     * noreply@hr.artisanbn.com (from MAIL_FROM_ADDRESS env).
     */
    public function mailTaskAssignees(Request $request, $id)
    {
        $task = Task::with(['assignees', 'checklists', 'results'])->find($id);

        if (! $task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        $preparedMails = $this->prepareAssigneeTaskMails($task);

        // deliverTestMail = true: record notification AND send email immediately
        $response = $this->dispatchTaskNotification(
            $request->merge(['task_id' => (int) $task->id]),
            self::TYPE_TASK_UPDATE,
            fn(Task $task, int $actorEid, array $validated) => $this->buildAssigneeChange($task, $actorEid, $validated),
            true
        );

        return $this->withPreparedTaskAssignMails($response, $preparedMails);
    }

    /**
     * Build one TaskAssignNotification per employee that is currently assigned
     * to the task.
     *
     * The recipients are resolved with buildRecipientRows(), the same helper the
     * notification pipeline already uses, so the assigned employee's email comes
     * from users.email through users.EID = employees.id. No new recipient query
     * is introduced here.
     *
     * The returned array is metadata only: it reports which employee the mail
     * was prepared for and which template it would use. Nothing is rendered to a
     * recipient and no Mailable delivery method is invoked.
     *
     * @return array<int, array<string, mixed>>
     */
    private function prepareAssigneeTaskMails(Task $task): array
    {
        $task->loadMissing(['assignees', 'checklists', 'results']);

        $employeeIds = $this->assigneeIds($task);

        if (empty($employeeIds)) {
            return [];
        }

        $actorEid = $this->currentEmployeeId();
        $data = $this->buildTaskData($task, $actorEid, self::TYPE_TASK_UPDATE);
        $assignedAt = $task->updated_at ? $task->updated_at->toDateTimeString() : now()->toDateTimeString();

        $recipients = collect($this->buildRecipientRows($employeeIds))->keyBy('employee_id');
        $prepared = [];

        foreach ($employeeIds as $employeeId) {
            $recipient = $recipients->get($employeeId);

            if (! $recipient) {
                continue;
            }

            // Constructed for representation only. Delivery stays disabled
            // inside the mailable, and nothing below ever sends or queues it.
            $mailable = new TaskAssignNotification(
                employeeId: $employeeId,
                recipientEmail: (string) ($recipient['email'] ?? ''),
                recipientName: (string) ($recipient['employee_name'] ?? ''),
                taskName: (string) ($data['task_name'] ?? ''),
                taskId: (int) ($data['task_id'] ?? $task->id),
                assignedByName: (string) ($data['assigned_by_name'] ?? ''),
                status: (string) ($data['status'] ?? ''),
                priority: (string) ($data['priority'] ?? ''),
                startDate: (string) ($data['start_date'] ?? ''),
                dueDate: (string) ($data['due_date'] ?? ''),
                assignedAt: $assignedAt,
                actionUrl: (string) ($this->taskActionUrl() ?? ''),
            );

            $prepared[] = [
                'employee_id' => $employeeId,
                'employee_name' => $mailable->recipientName,
                'employee_code' => $recipient['employee_code'] ?? null,
                'recipient_email_resolved' => $mailable->hasRecipientEmail(),
                'mailable' => TaskAssignNotification::class,
                'view' => $mailable::VIEW,
                'subject' => $mailable->subjectLine(),
                'delivery_enabled' => $mailable::DELIVERY_ENABLED,
                'sent' => false,
                'queued' => false,
            ];
        }

        return $prepared;
    }

    /**
     * Attach the prepared task assignment mail metadata to the notification
     * response. The recipient addresses themselves are never returned to the
     * caller, only whether one could be resolved.
     */
    private function withPreparedTaskAssignMails(JsonResponse $response, array $preparedMails): JsonResponse
    {
        $payload = $response->getData(true);

        if (isset($payload['data']['mail']) && is_array($payload['data']['mail'])) {
            $payload['data']['mail']['prepared'] = $preparedMails;
            $payload['data']['mail']['prepared_count'] = count($preparedMails);
        }

        return response()->json($payload, $response->getStatusCode());
    }

    /**
     * Shared pipeline for every task notification.
     *
     * 1. find the notification type
     * 2. stop when the type is missing or inactive
     * 3. create the notification
     * 4. create the notification recipient
     * 5. create the notification delivery row (channel = mail, status = pending)
     * 6. prepare the mail content
     * 7. send the restricted test mail and track the delivery result
     */
    private function dispatchTaskNotification(Request $request, string $typeName, callable $builder, bool $deliverTestMail = true): JsonResponse
    {
        $validated = $request->validate($this->notificationValidationRules($typeName));

        $task = Task::with(['assignees', 'checklists', 'results'])->find($validated['task_id']);

        if (! $task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        try {
            $payload = $builder($task, $this->currentEmployeeId(), $validated);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Task notification payload could not be built', [
                'notification_type' => $typeName,
                'task_id' => $task->id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The notification could not be prepared. Please try again later.',
            ], 422);
        }

        $recipients = $this->buildRecipientRows(
            $this->resolveRecipientEmployeeIds($payload['recipients'], $this->currentEmployeeId())
        );

        if (empty($recipients)) {
            return $this->skippedResponse($typeName, $task->id, 'no_recipient', 'No eligible recipient for this task event.');
        }

        $type = $this->findActiveNotificationType($typeName);

        if (! $type) {
            Log::info('Task notification skipped, type missing or inactive', [
                'notification_type' => $typeName,
                'task_id' => $task->id,
            ]);

            return $this->skippedResponse($typeName, $task->id, 'inactive_notification_type', 'Notification type is not active.');
        }

        $recipients = $this->filterDuplicateRecipients((int) $type->id, (int) $task->id, $recipients);

        if (empty($recipients)) {
            return $this->skippedResponse($typeName, $task->id, 'duplicate_suppressed', 'An identical notification was already prepared.');
        }

        try {
            $stored = DB::transaction(function () use ($type, $payload, $recipients) {
                return $this->persistNotification($type, $payload, $recipients);
            });
        } catch (QueryException $e) {
            Log::error('Task notification could not be stored', [
                'notification_type' => $typeName,
                'task_id' => $task->id,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The notification could not be prepared. Please try again later.',
            ], 422);
        }

        $deliveries = $deliverTestMail
            ? $this->prepareAndDeliverProductionMails($typeName, $payload, $stored['rows'], $recipients)
            : [];

        if (! $deliverTestMail) {
            Log::info('Task notification recorded without test mail delivery', [
                'notification_type' => $typeName,
                'notification_id' => $stored['notification_id'],
                'task_id' => $task->id,
                'reason' => 'automatic_trigger_no_test_mail',
            ]);
        }

        $sentCount = collect($deliveries)->where('sent', true)->count();
        $failedCount = collect($deliveries)->where('sent', false)->count();

        return response()->json([
            'success' => true,
            'message' => $this->deliveryOutcomeMessage($deliverTestMail, $sentCount, $failedCount),
            'data' => [
                'notification_type' => $typeName,
                'notification_type_id' => (int) $type->id,
                'notification_id' => $stored['notification_id'],
                'notification_created' => true,
                'title' => $payload['title'],
                'body' => $payload['body'],
                'recipients' => $this->hydrateDeliveryStatus($stored['rows'], $deliveries),
                'mail' => [
                    'sending_enabled' => self::MAIL_SENDING_ENABLED && $deliverTestMail,
                    'system_sender' => Config::get('mail.from.address'),
                    'queued' => false,
                    'sent' => $sentCount,
                    'failed' => $failedCount,
                    'deferred' => ! $deliverTestMail,
                ],
            ],
        ]);
    }

    /**
     * Create notifications, recipients and deliveries in a single transaction
     * so a partial notification can never be left behind.
     */
    private function persistNotification(object $type, array $payload, array $recipients): array
    {
        $notificationId = DB::table('notifications')->insertGetId([
            'notification_type_id' => (int) $type->id,
            'title' => $payload['title'],
            'body' => $payload['body'],
            'data' => $this->encodeJson($payload['data']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rows = [];

        foreach ($recipients as $recipient) {
            $recipientId = DB::table('notification_recipients')->insertGetId([
                'notification_id' => $notificationId,
                'user_id' => $recipient['user_id'],
                'status' => self::RECIPIENT_STATUS_PENDING,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $deliveryId = DB::table('notification_deliveries')->insertGetId([
                'notification_recipient_id' => $recipientId,
                'channel' => self::DELIVERY_CHANNEL_MAIL,
                'status' => self::DELIVERY_STATUS_PENDING,
                'attempts' => 0,
                'sent_at' => null,
                'delivered_at' => null,
                'failed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rows[] = [
                'notification_id' => $notificationId,
                'notification_recipient_id' => $recipientId,
                'delivery_id' => $deliveryId,
                'user_id' => $recipient['user_id'],
                'employee_id' => $recipient['employee_id'],
                'employee_name' => $recipient['employee_name'],
                'employee_code' => $recipient['employee_code'],
                'channel' => self::DELIVERY_CHANNEL_MAIL,
                'status' => self::DELIVERY_STATUS_PENDING,
                'attempts' => 0,
                'sent_at' => null,
                'delivered_at' => null,
                'failed_at' => null,
            ];
        }

        return ['notification_id' => $notificationId, 'rows' => $rows];
    }

    /**
     * Prepare the mail content and deliver the production mail for every
     * stored recipient.
     *
     * A failure is isolated per recipient: the notification rows already exist,
     * the delivery row is marked failed with its attempt count and timestamp,
     * and the remaining recipients are still processed. No SMTP credential or
     * mail configuration detail is ever returned to the caller.
     */
    private function prepareAndDeliverProductionMails(string $typeName, array $payload, array $rows, array $recipients): array
    {
        $recipientsByEmployee = collect($recipients)->keyBy('employee_id');
        $deliveries = [];

        foreach ($rows as $row) {
            $recipient = $recipientsByEmployee->get($row['employee_id']);

            if (! $recipient) {
                continue;
            }

            try {
                $mail = $this->prepareProductionMail($typeName, $payload, $recipient);
                $deliveries[] = $this->sendProductionMail($mail, (int) $row['delivery_id'], $recipient);
            } catch (\Throwable $e) {
                Log::error('Task production mail preparation failed', [
                    'notification_type' => $typeName,
                    'notification_id' => $row['notification_id'],
                    'employee_id' => $row['employee_id'],
                    'exception' => $e->getMessage(),
                ]);

                $deliveries[] = $this->markDeliveryFailed((int) $row['delivery_id'], $e);
            }
        }

        return $deliveries;
    }

    /**
     * Brand palette. Inline CSS only, so the template has no external
     * dependency, no web font and no remote image.
     */
    private const MAIL_INK = '#0f172a';

    private const MAIL_BODY = '#334155';

    private const MAIL_MUTED = '#64748b';

    private const MAIL_LINE = '#e2e8f0';

    private const MAIL_SOFT = '#f8fafc';

    /**
     * Assemble the view model for the production mail.
     *
     * One view model is built for every notification type and rendered by a
     * single layout, so the presentation can never drift between types.
     */
    private function prepareProductionMail(string $typeName, array $payload, array $recipient): array
    {
        $data = $payload['data'];
        $presentation = $this->notificationPresentation($typeName);
        $taskName = (string) ($data['task_name'] ?? '');

        return [
            'notification_type' => $typeName,
            'channel' => self::DELIVERY_CHANNEL_MAIL,
            'notification_recipient_user_id' => $recipient['user_id'],
            'notification_recipient_employee_id' => $recipient['employee_id'],
            'notification_recipient_name' => $recipient['employee_name'],
            'notification_recipient_email' => $recipient['email'],
            'subject' => '[Artisan HRIS] ' . $presentation['subject'] . ': ' . $taskName,
            'title' => $payload['title'],
            'body' => $payload['body'],
            'data' => $data,
            'presentation' => $presentation,
            'task_name' => $taskName,
            'status' => $data['status'] ?? null,
            'priority' => $data['priority'] ?? null,
            'summary' => $this->taskDetailRows($data),
            'checklist' => $this->checklistSection($data),
            'completion' => $this->completionSection($data),
            'performance' => $this->performanceSection($data),
            'description' => $this->descriptionParagraph($data),
            'assignees' => $this->assigneeList($data),
            'action_url' => $this->taskActionUrl(),
            'fields' => $this->taskDetailRows($data),
            'prepared_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Per type eyebrow, headline, subject prefix and accent colour.
     *
     * The heading itself always comes from the notification title built for the
     * real payload, so the wording stays driven by the notification data.
     */
    private function notificationPresentation(string $typeName): array
    {
        return match ($typeName) {
            self::TYPE_TASK_CREATE => [
                'eyebrow' => 'Task Created',
                'headline' => 'A new task has been assigned to you.',
                'subject' => 'New Task Assigned',
                'accent' => '#2563eb',
                'tint' => '#eff6ff',
            ],
            self::TYPE_TASK_UPDATE => [
                'eyebrow' => 'Task Updated',
                'headline' => 'A task you are involved in has been updated.',
                'subject' => 'Task Updated',
                'accent' => '#4f46e5',
                'tint' => '#eef2ff',
            ],
            self::TYPE_NEW_CHECKLIST => [
                'eyebrow' => 'New Checklist',
                'headline' => 'A new checklist item has been added to a task.',
                'subject' => 'New Checklist Added',
                'accent' => '#0e7490',
                'tint' => '#ecfeff',
            ],
            self::TYPE_TASK_COMPLETE => [
                'eyebrow' => 'Task Completed',
                'headline' => 'A task has been marked as completed.',
                'subject' => 'Task Completed',
                'accent' => '#15803d',
                'tint' => '#f0fdf4',
            ],
            self::TYPE_UPDATED_PERFORMS => [
                'eyebrow' => 'Performance Updated',
                'headline' => 'Task progress and performance information has been updated.',
                'subject' => 'Task Performance Updated',
                'accent' => '#6d28d9',
                'tint' => '#f5f3ff',
            ],
            default => [
                'eyebrow' => 'Task Notification',
                'headline' => 'A task you are involved in has been updated.',
                'subject' => 'Task Notification',
                'accent' => '#334155',
                'tint' => self::MAIL_SOFT,
            ],
        };
    }

    /**
     * Task summary rows. Only real task columns are used, and a row is dropped
     * when the underlying value is missing, so no empty field is rendered.
     */
    private function taskDetailRows(array $data): array
    {
        $rows = [
            'Assigned to' => $this->joinNames($data['assigned_to_names'] ?? []),
            'Assigned by' => $data['assigned_by_name'] ?? null,
            'Priority' => $data['priority'] ?? null,
            'Bucket' => $data['bucket'] ?? null,
            'Start date' => $this->displayDate($data['start_date'] ?? null),
            'Due date' => $this->displayDate($data['due_date'] ?? null),
            'Revised due date' => $this->displayDate($data['new_due_date'] ?? null),
            'Repeat' => $data['repeat'] ?? null,
            'Privacy' => $data['privacy'] ?? null,
            'Created at' => $this->displayDateTime($data['created_at'] ?? null),
            'Last updated' => $this->displayDateTime($data['updated_at'] ?? null),
        ];

        // Never show a revised due date that is identical to the due date.
        if (($rows['Revised due date'] ?? null) === ($rows['Due date'] ?? null)) {
            unset($rows['Revised due date']);
        }

        return array_filter($rows, fn($value) => $value !== null && $value !== '' && $value !== 'N/A');
    }

    /**
     * Checklist block, only when the notification actually carries a checklist.
     */
    private function checklistSection(array $data): array
    {
        if (empty($data['checklist_name'])) {
            return [];
        }

        $rows = [
            'Checklist item' => (string) $data['checklist_name'],
            'Created by' => $data['checklist_created_by_name'] ?? null,
            'Added at' => $this->displayDateTime($data['checklist_created_at'] ?? null),
            'Checklist status' => ($data['checklist_completed'] ?? false) ? 'Completed' : 'Outstanding',
        ];

        return array_filter($rows, fn($value) => $value !== null && $value !== '');
    }

    /**
     * Completion block. Returns nothing when the payload has no completion
     * information, so the section is omitted instead of rendered empty.
     */
    private function completionSection(array $data): array
    {
        $rows = [
            'Task status' => $data['status'] ?? null,
            'Completed by' => $data['completed_by_name'] ?? null,
            'Completed at' => $this->displayDateTime($data['completed_at'] ?? null),
        ];

        $rows = array_filter($rows, fn($value) => $value !== null && $value !== '' && $value !== 'N/A');

        return count($rows) > 1 ? $rows : [];
    }

    /**
     * Performance block built from the columns that really exist on
     * tasks_results and tasks_checklists. There is no stored percentage and no
     * stored previous progress in the schema, so none is invented here.
     */
    private function performanceSection(array $data): array
    {
        $percent = (int) ($data['checklist_progress_percent'] ?? 0);
        $total = (int) ($data['checklist_total'] ?? 0);

        if ($total < 1) {
            return [];
        }

        return [
            'percent' => max(0, min(100, $percent)),
            'completed' => (int) ($data['checklist_completed'] ?? 0),
            'total' => $total,
            'rows' => array_filter([
                'Performed by' => $data['performed_by_name'] ?? null,
                'Performed at' => $this->displayDate($data['performed_at'] ?? null),
                'Reported status' => $data['reported_status'] ?? null,
            ], fn($value) => $value !== null && $value !== ''),
        ];
    }

    private function descriptionParagraph(array $data): ?string
    {
        $notes = $data['description'] ?? null;

        return $notes ? (string) $notes : null;
    }

    /**
     * Assignee breakdown, only meaningful when a task has more than one owner.
     *
     * @return array<int, array<string, string>>
     */
    private function assigneeList(array $data): array
    {
        $assignees = $data['assignees'] ?? [];

        if (count($assignees) < 2) {
            return [];
        }

        return collect($assignees)
            ->map(fn(array $assignee) => [
                'name' => (string) ($assignee['employee_name'] ?? 'Unknown'),
                'code' => (string) ($assignee['employee_code'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * Call to action target.
     *
     * /planner/my-tasks is an existing front-end route (resources/js/router.js).
     * The base URL is taken from the application configuration, so no route is
     * invented and no configuration file has to change. When the configured
     * base is unusable the button is simply omitted.
     */
    private function taskActionUrl(): ?string
    {
        $base = rtrim((string) config('app.url'), '/');

        return preg_match('#^https?://#i', $base) ? $base . '/planner/my-tasks' : null;
    }

    /**
     * Render the shared HTML layout for every task notification type.
     */
    private function renderTaskMailHtml(array $mail): string
    {
        $view = $this->notificationPresentation($mail['notification_type']);
        $accent = $view['accent'];
        $tint = $view['tint'];
        $brand = Config::get('app.name', 'Artisan HRIS');

        $sections = '';

        if (! empty($mail['summary'])) {
            $sections .= $this->mailCard('Task summary', null, $this->mailRows($mail['summary']));
        }

        if (! empty($mail['description'])) {
            $sections .= $this->mailCard(
                'Task description',
                null,
                '<p style="margin:0;font-size:14px;line-height:22px;color:' . self::MAIL_BODY . ';">'
                    . nl2br(e($mail['description'])) . '</p>'
            );
        }

        if (! empty($mail['checklist'])) {
            $sections .= $this->mailCard('Checklist', null, $this->mailRows($mail['checklist']));
        }

        if (! empty($mail['completion'])) {
            $sections .= $this->mailCard('Completion', null, $this->mailRows($mail['completion']));
        }

        if (! empty($mail['performance'])) {
            $performance = $mail['performance'];
            $sections .= $this->mailCard(
                'Performance',
                null,
                $this->mailProgressBar($performance['percent'], $accent)
                    . '<p style="margin:0 0 16px 0;font-size:14px;line-height:20px;color:' . self::MAIL_BODY . ';">'
                    . e($performance['completed']) . ' of ' . $performance['total'] . ' checklist items completed ('
                    . e($performance['percent']) . '%).</p>'
                    . $this->mailRows($performance['rows'])
            );
        }

        if (! empty($mail['assignees'])) {
            $items = '';

            foreach ($mail['assignees'] as $assignee) {
                $code = $assignee['code'] !== '' ? ' <span style="color:' . self::MAIL_MUTED . ';">(' . $this->esc($assignee['code']) . ')</span>' : '';
                $items .= '<li style="margin:0 0 6px 0;font-size:14px;line-height:20px;color:' . self::MAIL_BODY . ';">'
                    . $this->esc($assignee['name']) . $code . '</li>';
            }

            $sections .= $this->mailCard(
                'Assignees',
                null,
                '<ul style="margin:0;padding:0 0 0 18px;">' . $items . '</ul>'
            );
        }

        $action = $mail['action_url'] ? $this->mailButton('View Task', $mail['action_url'], $accent) : '';

        return $this->mailShell(
            $this->mailPreheader($mail['title'] . ' - ' . $mail['task_name'])
                . $this->mailHeader($brand, $accent)
                . $this->mailHero($mail, $view, $accent, $tint)
                . '<tr><td style="padding:0 24px 24px 24px;">' . $sections . $action . '</td></tr>'
                . $this->mailTestNotice()
                . $this->mailFooter($brand),
            $this->esc($mail['title'] . ' - ' . $mail['task_name'])
        );
    }

    private function mailShell(string $body, string $title): string
    {
        return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
            . '<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><head>'
            . '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'
            . '<meta name="viewport" content="width=device-width, initial-scale=1" />'
            . '<meta name="color-scheme" content="light" />'
            . '<title>' . $title . '</title>'
            . '<!--[if mso]><style>body,table,td{font-family:Arial,sans-serif !important;}.wrap{width:600px !important;}</style><![endif]-->'
            . '<style>'
            . '@media only screen and (max-width:620px){'
            . '.wrap{width:100% !important;}.pad{padding-left:16px !important;padding-right:16px !important;}'
            . '.hero-title{font-size:20px !important;line-height:28px !important;}.stack{width:100% !important;display:block !important;}'
            . '.btn{width:100% !important;display:block !important;box-sizing:border-box !important;}'
            . '}'
            . '</style></head>'
            . '<body style="margin:0;padding:0;background-color:#eef2f7;'
            . '-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">'
            . $body
            . '</body></html>';
    }

    private function mailPreheader(string $text): string
    {
        return '<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#eef2f7;">'
            . $this->esc($text) . '</div>';
    }

    private function mailHeader(string $brand, string $accent): string
    {
        return '<tr><td class="pad" style="padding:0;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background-color:#0f2942;border-radius:12px 12px 0 0;">'
            . '<tr><td style="height:4px;font-size:0;line-height:0;background-color:' . $accent . ';">&nbsp;</td></tr>'
            . '<tr><td style="padding:26px 24px 24px 24px;">'
            . '<p style="margin:0 0 6px 0;font-family:Arial,Helvetica,sans-serif;font-size:19px;line-height:24px;font-weight:bold;letter-spacing:0.4px;color:#ffffff;">'
            . $this->esc($brand) . '</p>'
            . '<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;letter-spacing:1.4px;text-transform:uppercase;color:#93b4d6;">'
            . 'Task Notification</p>'
            . '</td></tr></table></td></tr>';
    }

    private function mailHero(array $mail, array $view, string $accent, string $tint): string
    {
        $status = trim((string) ($mail['status'] ?? ''));
        $title = trim((string) $mail['title']);

        // The pill already carries the type label, so the heading is only
        // repeated when the notification title says something different.
        $heading = strcasecmp($title, $view['eyebrow']) === 0
            ? ''
            : '<h1 class="hero-title" style="margin:0 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:23px;line-height:30px;font-weight:bold;color:' . self::MAIL_INK . ';">'
            . $this->esc($title) . '</h1>';

        return '<tr><td class="pad" style="padding:0;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background-color:#ffffff;border-left:1px solid ' . self::MAIL_LINE . ';border-right:1px solid ' . self::MAIL_LINE . ';">'
            . '<tr><td style="padding:24px;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 14px 0;"><tr>'
            . '<td style="background-color:' . $tint . ';border:1px solid ' . $accent . ';border-radius:20px;padding:5px 12px;">'
            . '<span style="font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:14px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:' . $accent . ';">'
            . $this->esc($view['eyebrow']) . '</span></td></tr></table>'
            . $heading
            . '<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:23px;color:' . self::MAIL_BODY . ';">'
            . $this->esc($view['headline']) . '</p>'
            . '<p style="margin:12px 0 0 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:' . self::MAIL_BODY . ';">'
            . $this->esc($mail['body']) . '</p>'
            . ($status !== '' ? '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:16px 0 0 0;"><tr>' . $this->mailStatusBadge($status) . '</tr></table>' : '')
            . '</td></tr></table></td></tr>'
            . '<tr><td class="pad" style="padding:20px 24px 4px 24px;">'
            . '<p style="margin:0 0 4px 0;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:16px;letter-spacing:1.2px;text-transform:uppercase;color:' . self::MAIL_MUTED . ';">'
            . 'Task</p>'
            . '<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:19px;line-height:26px;font-weight:bold;color:' . self::MAIL_INK . ';">'
            . $this->esc($mail['task_name']) . '</p>'
            . '</td></tr>';
    }

    /**
     * Status badge built from the live task status, never hard coded.
     */
    private function mailStatusBadge(string $status): string
    {
        $tone = $this->statusTone($status);

        return '<td style="background-color:' . $tone['bg'] . ';border:1px solid ' . $tone['bd'] . ';border-radius:6px;padding:6px 12px;">'
            . '<span style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:16px;font-weight:bold;letter-spacing:0.6px;text-transform:uppercase;color:' . $tone['fg'] . '">'
            . $this->esc($status) . '</span></td>';
    }

    private function statusTone(?string $status): array
    {
        return match (strtolower(trim((string) $status))) {
            'completed' => ['bg' => '#dcfce7', 'fg' => '#14532d', 'bd' => '#bbf7d0'],
            'in progress' => ['bg' => '#dbeafe', 'fg' => '#1e3a8a', 'bd' => '#bfdbfe'],
            'not started' => ['bg' => '#fef3c7', 'fg' => '#78350f', 'bd' => '#fde68a'],
            default => ['bg' => '#f1f5f9', 'fg' => '#334155', 'bd' => self::MAIL_LINE],
        };
    }

    private function mailCard(string $label, ?string $title, string $inner): string
    {
        $heading = $label;

        if ($title) {
            $heading = $label . ' - ' . $title;
        }

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:0 0 16px 0;'
            . 'background-color:' . self::MAIL_SOFT . ';border:1px solid ' . self::MAIL_LINE . ';border-radius:10px;">'
            . '<tr><td style="padding:18px 20px;">'
            . '<p style="margin:0 0 12px 0;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:16px;letter-spacing:1.2px;text-transform:uppercase;color:' . self::MAIL_MUTED . ';">'
            . $this->esc($heading) . '</p>'
            . $inner
            . '</td></tr></table>';
    }

    /**
     * @param  array<string, string>  $rows
     */
    private function mailRows(array $rows): string
    {
        $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">';

        foreach ($rows as $label => $value) {
            $html .= '<tr>'
                . '<td class="stack" width="42%" valign="top" style="padding:7px 12px 7px 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:19px;color:' . self::MAIL_MUTED . ';">'
                . $this->esc($label) . '</td>'
                . '<td class="stack" valign="top" style="padding:7px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:19px;font-weight:bold;color:' . self::MAIL_INK . ';">'
                . $this->esc($value) . '</td>'
                . '</tr>';
        }

        return $html . '</table>';
    }

    private function mailProgressBar(int $percent, string $accent): string
    {
        $percent = max(0, min(100, $percent));
        $rest = 100 - $percent;

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;margin:0 0 16px 0;">'
            . '<tr>'
            . '<td width="' . $percent . '%" height="8" style="height:8px;font-size:0;line-height:0;background-color:' . $accent . ';">&nbsp;</td>'
            . '<td width="' . $rest . '%" height="8" style="height:8px;font-size:0;line-height:0;background-color:' . self::MAIL_LINE . ';">&nbsp;</td>'
            . '</tr></table>';
    }

    /**
     * Table based button with a VML fallback so Outlook renders it as a real
     * button instead of a bare link.
     */
    private function mailButton(string $label, string $url, string $accent): string
    {
        $safe = $this->esc($url);

        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 0 0;">'
            . '<tr><td style="border-radius:8px;background-color:' . $accent . ';">'
            . '<a class="btn" href="' . $safe . '" target="_blank" style="display:inline-block;padding:13px 26px;font-family:Arial,Helvetica,sans-serif;'
            . 'font-size:15px;line-height:18px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:8px;">'
            . $this->esc($label) . '</a>'
            . '</td></tr></table>'
            . '<div style="display:none;mso-hide:all;">'
            . '<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="' . $safe . '" '
            . 'style="height:46px;v-text-anchor:middle;width:180px;" arcsize="17%" strokecolor="' . $accent . '" fillcolor="' . $accent . '">'
            . '<w:anchorlock/><center style="color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:bold;">'
            . $this->esc($label) . '</center></v:roundrect></div>';
    }

    /**
     * Development notice. Delivery is pinned to the test addresses, so the
     * mail says so plainly instead of pretending to reach the assignee.
     */
    private function mailTestNotice(): string
    {
        return '<tr><td class="pad" style="padding:0 24px 24px 24px;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background-color:#fffbeb;border:1px solid #fde68a;border-radius:10px;">'
            . '<tr><td style="padding:14px 18px;">'
            . '<p style="margin:0 0 4px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;font-weight:bold;color:#92400e;"></p>'
            . '<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#92400e;"> </p>'
            . '</td></tr></table></td></tr>';
        // return '<tr><td class="pad" style="padding:0 24px 24px 24px;">'
        //     . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background-color:#fffbeb;border:1px solid #fde68a;border-radius:10px;">'
        //     . '<tr><td style="padding:14px 18px;">'
        //     . '<p style="margin:0 0 4px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;font-weight:bold;color:#92400e;">'
        //     . 'Development test mail</p>'
        //     . '<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#92400e;">'
        //     . 'This notification is recorded for the real recipient in the system. '
        //     . 'Test mail delivery is currently disabled.</p>'
        //     . '</td></tr></table></td></tr>';
    }

    private function mailFooter(string $brand): string
    {
        return '<tr><td class="pad" style="padding:0;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background-color:#0f2942;border-radius:0 0 12px 12px;">'
            . '<tr><td style="padding:22px 24px;">'
            . '<p style="margin:0 0 6px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:20px;font-weight:bold;color:#ffffff;">'
            . $this->esc($brand) . '</p>'
            . '<p style="margin:0 0 4px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#c3d5e6;">'
            . 'This is an automated notification from the ' . e($brand) . ' system.</p>'
            . '<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#c3d5e6;">'
            . 'Please do not reply to this email.</p>'
            . '</td></tr></table></td></tr>'
            . '<tr><td class="pad" style="padding:16px 24px 0 24px;">'
            . '<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:17px;color:#94a3b8;">'
            . 'Delivery channel: ' . e(self::DELIVERY_CHANNEL_MAIL) . ' &middot; Prepared at ' . e(now()->toDateTimeString()) . '</p>'
            . '</td></tr>';
    }

    private function esc($value): string
    {
        return e((string) $value);
    }

    /**
     * Human readable date. Only existing task columns are formatted.
     */
    private function displayDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('j F Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function displayDateTime($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('j F Y, g:i A');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    /**
     * Send the production task assignment mail to the actual recipient.
     *
     * Uses the system sender (noreply@hr.artisanbn.com from MAIL_FROM_ADDRESS env)
     * and delivers to the assigned employee's employees.Official_Email.
     *
     * Delivery tracking around the attempt:
     *   before  -> status pending, attempts 0, sent_at/delivered_at/failed_at NULL
     *   attempt -> attempts incremented, sent_at set
     *   success -> status success, delivered_at set
     *   failure -> status failed, failed_at set
     */
    private function sendProductionMail(array $mail, int $deliveryId, array $recipient): array
    {
        if (! self::MAIL_SENDING_ENABLED) {
            Log::info('Task production mail skipped, MAIL_SENDING_ENABLED is false', [
                'notification_type' => $mail['notification_type'],
                'delivery_id' => $deliveryId,
            ]);

            return [
                'delivery_id' => $deliveryId,
                'sent' => false,
                'status' => self::DELIVERY_STATUS_PENDING,
                'attempts' => $this->currentAttempts($deliveryId),
            ];
        }

        $this->markDeliveryAttempted($deliveryId);

        try {
            Mail::html($this->renderTaskMailHtml($mail), function (Message $message) use ($mail, $recipient) {
                $message->to($recipient['email'], $recipient['employee_name'])
                    ->subject($mail['subject']);
            });
        } catch (\Throwable $e) {
            return $this->markDeliveryFailed($deliveryId, $e, $mail);
        }

        return $this->markDeliverySucceeded($deliveryId, $mail, $recipient);
    }

    /**
     * Increment the attempt counter and stamp sent_at before the transport is
     * touched, so an attempt is always visible even if the transport dies.
     */
    private function markDeliveryAttempted(int $deliveryId): void
    {
        $attempts = min($this->currentAttempts($deliveryId) + 1, 255);

        DB::table('notification_deliveries')->where('id', $deliveryId)->update([
            'attempts' => $attempts,
            'sent_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function markDeliverySucceeded(int $deliveryId, array $mail, array $recipient = []): array
    {
        DB::table('notification_deliveries')->where('id', $deliveryId)->update([
            'status' => self::DELIVERY_STATUS_SUCCESS,
            'delivered_at' => now(),
            'failed_at' => null,
            'updated_at' => now(),
        ]);

        Log::info('Task production mail delivered', [
            'notification_type' => $mail['notification_type'],
            'delivery_id' => $deliveryId,
            'from' => Config::get('mail.from.address'),
            'to' => $recipient['email'] ?? 'unknown',
            'notification_recipient_user_id' => $mail['notification_recipient_user_id'],
        ]);

        return [
            'delivery_id' => $deliveryId,
            'sent' => true,
            'status' => self::DELIVERY_STATUS_SUCCESS,
            'attempts' => $this->currentAttempts($deliveryId),
        ];
    }

    private function markDeliveryFailed(int $deliveryId, \Throwable $e, array $mail = []): array
    {
        DB::table('notification_deliveries')->where('id', $deliveryId)->update([
            'status' => self::DELIVERY_STATUS_FAILED,
            'failed_at' => now(),
            'delivered_at' => null,
            'updated_at' => now(),
        ]);

        Log::error('Task production mail delivery failed', [
            'notification_type' => $mail['notification_type'] ?? null,
            'delivery_id' => $deliveryId,
            'from' => Config::get('mail.from.address'),
            'exception' => $e->getMessage(),
        ]);

        return [
            'delivery_id' => $deliveryId,
            'sent' => false,
            'status' => self::DELIVERY_STATUS_FAILED,
            'attempts' => $this->currentAttempts($deliveryId),
        ];
    }

    private function currentAttempts(int $deliveryId): int
    {
        return (int) DB::table('notification_deliveries')->where('id', $deliveryId)->value('attempts');
    }

    /**
     * Merge the live delivery state into the API payload so the response
     * reflects what really happened to the test mail.
     */
    private function hydrateDeliveryStatus(array $rows, array $deliveries): array
    {
        $byDelivery = collect($deliveries)->keyBy('delivery_id');

        return array_map(function (array $row) use ($byDelivery) {
            $delivery = DB::table('notification_deliveries')->where('id', $row['delivery_id'])->first();

            $row['status'] = $delivery ? $delivery->status : self::DELIVERY_STATUS_PENDING;
            $row['attempts'] = $delivery ? (int) $delivery->attempts : 0;
            $row['sent_at'] = $delivery ? $this->formatTimestamp($delivery->sent_at) : null;
            $row['delivered_at'] = $delivery ? $this->formatTimestamp($delivery->delivered_at) : null;
            $row['failed_at'] = $delivery ? $this->formatTimestamp($delivery->failed_at) : null;
            $row['test_mail_sent'] = $byDelivery->get($row['delivery_id'])['sent'] ?? false;

            return $row;
        }, $rows);
    }

    /**
     * Resolve the notification type and refuse anything that is not active.
     */
    private function findActiveNotificationType(string $name): ?object
    {
        return DB::table('notification_types')
            ->where('name', $name)
            ->where('code', self::NOTIFICATION_CODE)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array<int, string>
     */
    private function notificationTypeNames(): array
    {
        return [
            self::TYPE_TASK_CREATE,
            self::TYPE_TASK_UPDATE,
            self::TYPE_NEW_CHECKLIST,
            self::TYPE_TASK_COMPLETE,
            self::TYPE_UPDATED_PERFORMS,
        ];
    }

    /**
     * Employee ids of the people that must be informed about a task event.
     * The acting user is removed so nobody is notified about their own action.
     */
    private function resolveRecipientEmployeeIds(array $employeeIds, ?int $actorEid): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $employeeIds),
            fn($id) => $id > 0
        )));

        if ($actorEid) {
            $ids = array_values(array_filter($ids, fn($id) => $id !== $actorEid));
        }

        return $ids;
    }

    /**
     * Map employee ids to notification_recipients.user_id (which stores employees.id)
     * and resolve recipient email from employees.Official_Email.
     *
     * Employees without an Official_Email are skipped and logged.
     */
    private function buildRecipientRows(array $employeeIds): array
    {
        if (empty($employeeIds)) {
            return [];
        }

        $employees = employee::select('id', 'Full_Name', 'Employee_Id', 'Official_Email')
            ->whereIn('id', $employeeIds)
            ->get()
            ->keyBy('id');

        $rows = [];

        foreach ($employeeIds as $employeeId) {
            $employee = $employees->get($employeeId);

            if (! $employee) {
                Log::warning('Task notification recipient skipped, employee not found', [
                    'employee_id' => $employeeId,
                ]);

                continue;
            }

            $officialMail = $employee->Official_Email ?? null;

            if (! $officialMail || ! filter_var($officialMail, FILTER_VALIDATE_EMAIL)) {
                Log::warning('Task notification recipient skipped, no valid Official_Email', [
                    'employee_id' => $employeeId,
                ]);

                continue;
            }

            $rows[] = [
                'user_id' => (int) $employee->id,
                'employee_id' => $employeeId,
                'employee_name' => $employee->Full_Name,
                'employee_code' => $employee->Employee_Id,
                'email' => $officialMail,
            ];
        }

        return $rows;
    }

    /**
     * Drop candidates that already received the same notification type for the
     * same task inside the duplicate window.
     */
    private function filterDuplicateRecipients(int $typeId, int $taskId, array $candidates): array
    {
        $driver = DB::connection()->getDriverName();
        $threshold = now()->subSeconds(self::DUPLICATE_WINDOW_SECONDS)->toDateTimeString();

        return array_values(array_filter($candidates, function ($candidate) use ($typeId, $taskId, $driver, $threshold) {
            $query = DB::table('notifications')
                ->join('notification_recipients', 'notification_recipients.notification_id', '=', 'notifications.id')
                ->where('notifications.notification_type_id', $typeId)
                ->where('notification_recipients.user_id', $candidate['user_id'])
                ->where('notifications.created_at', '>=', $threshold);

            if ($driver === 'mysql' || $driver === 'mariadb') {
                $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(notifications.data, "$.task_id")) = ?', [$taskId]);
            } else {
                $query->where('notifications.data', 'like', '%"task_id":' . $taskId . '%');
            }

            return ! $query->exists();
        }));
    }

    private function notificationValidationRules(string $typeName): array
    {
        $rules = [
            'task_id' => 'required|integer',
        ];

        if ($typeName === self::TYPE_NEW_CHECKLIST) {
            $rules['checklist_id'] = 'nullable|integer';
        }

        if ($typeName === self::TYPE_TASK_UPDATE) {
            $rules['changed_fields'] = 'nullable|array';
            $rules['changed_fields.*'] = 'string';
        }

        return $rules;
    }

    /**
     * Human readable summary of what happened to the delivery rows.
     */
    private function deliveryOutcomeMessage(bool $deliverTestMail, int $sentCount, int $failedCount): string
    {
        if (! self::MAIL_SENDING_ENABLED && ! $deliverTestMail) {
            return 'Notification recorded. No email was sent or queued.';
        }

        if (! $deliverTestMail) {
            return 'Notification recorded. Mail was not sent because this endpoint is triggered automatically during normal task usage.';
        }

        if ($failedCount > 0) {
            return 'Notification prepared. The mail was delivered for some recipients and failed for others.';
        }

        return 'Notification prepared and the mail was delivered.';
    }

    private function skippedResponse(string $typeName, int $taskId, string $reason, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'notification_type' => $typeName,
                'task_id' => $taskId,
                'notification_created' => false,
                'reason' => $reason,
                'recipients' => [],
                'mail' => [
                    'sending_enabled' => self::MAIL_SENDING_ENABLED,
                    'system_sender' => Config::get('mail.from.address'),
                    'sent' => 0,
                    'failed' => 0,
                    'queued' => false,
                ],
            ],
        ]);
    }

    private function buildTaskCreate(Task $task, int $actorEid, array $validated): array
    {
        $actorName = $this->employeeName($actorEid) ?? 'A supervisor';

        $data = $this->buildTaskData($task, $actorEid, self::TYPE_TASK_CREATE);
        $data['assigned_date'] = $task->created_at ? $task->created_at->toDateTimeString() : null;
        $data['trigger'] = 'task_created';

        return [
            'recipients' => $this->assigneeIds($task),
            'title' => 'Task Created',
            'body' => 'A new task "' . $task->task_name . '" (#' . $task->id . ') has been assigned to you by ' . $actorName . '.',
            'data' => $data,
        ];
    }

    private function buildTaskUpdate(Task $task, int $actorEid, array $validated): array
    {
        $changed = $validated['changed_fields'] ?? [];
        $actorName = $this->employeeName($actorEid) ?? 'A supervisor';

        $data = $this->buildTaskData($task, $actorEid, self::TYPE_TASK_UPDATE);
        $data['changed_fields'] = array_values($changed);
        $data['trigger'] = 'task_updated';

        $body = 'The task "' . $task->task_name . '" (#' . $task->id . ') has been updated by ' . $actorName . '.';

        if (! empty($changed)) {
            $body .= ' Changed: ' . $this->joinNames($changed) . '.';
        }

        return [
            'recipients' => $this->stakeholderEmployeeIds($task),
            'title' => 'Task Updated',
            'body' => $body,
            'data' => $data,
        ];
    }

    private function buildAssigneeChange(Task $task, int $actorEid, array $validated): array
    {
        $actorName = $this->employeeName($actorEid) ?? 'A supervisor';

        $data = $this->buildTaskData($task, $actorEid, self::TYPE_TASK_UPDATE);
        $data['changed_fields'] = ['assignees'];
        $data['trigger'] = 'assignees_saved';

        return [
            'recipients' => $this->assigneeIds($task),
            'title' => 'Task Assignment Updated',
            'body' => 'The assignee list of the task "' . $task->task_name . '" (#' . $task->id . ') was updated by ' . $actorName . '. You are currently assigned to it.',
            'data' => $data,
        ];
    }

    private function buildNewChecklist(Task $task, int $actorEid, array $validated): array
    {
        $checklist = $this->resolveChecklist($task, $validated['checklist_id'] ?? null);

        $actorName = $this->employeeName((int) $checklist->created_by) ?? $this->employeeName($actorEid) ?? 'A supervisor';

        $data = $this->buildTaskData($task, $actorEid, self::TYPE_NEW_CHECKLIST);
        $data['checklist_id'] = (int) $checklist->id;
        $data['checklist_name'] = $checklist->checklist_name;
        $data['checklist_completed'] = (bool) $checklist->completed_status;
        $data['checklist_completed_by'] = $checklist->completed_by ? (int) $checklist->completed_by : null;
        $data['checklist_created_by'] = (int) $checklist->created_by;
        $data['checklist_created_by_name'] = $this->employeeName((int) $checklist->created_by);
        $data['checklist_created_at'] = $checklist->created_at ? $checklist->created_at->toDateTimeString() : null;
        $data['trigger'] = 'checklist_created';

        return [
            'recipients' => $this->stakeholderEmployeeIds($task),
            'title' => 'New Checklist Item Added',
            'body' => 'A new checklist item "' . $checklist->checklist_name . '" was added to the task "' . $task->task_name . '" (#' . $task->id . ') by ' . $actorName . '.',
            'data' => $data,
        ];
    }

    private function buildTaskComplete(Task $task, int $actorEid, array $validated): array
    {
        $result = $this->latestResult($task);
        $actorName = $this->employeeName($actorEid) ?? 'A supervisor';

        $data = $this->buildTaskData($task, $actorEid, self::TYPE_TASK_COMPLETE);
        $data['completed_by'] = $actorEid;
        $data['completed_by_name'] = $actorName;
        $data['completed_at'] = $this->resolveCompletedAt($task, $result);
        $data['result_id'] = $result ? (int) $result->id : null;
        $data['trigger'] = 'task_completed';

        $body = 'The task "' . $task->task_name . '" (#' . $task->id . ') was marked as ' . $task->status . ' by ' . $actorName . '.';

        if ($task->status !== Task::STATUS_COMPLETED) {
            Log::info('Task completion notification stored while the task is not completed yet', [
                'task_id' => $task->id,
                'status' => $task->status,
            ]);

            $body .= ' The current stored status is still "' . $task->status . '".';
        }

        return [
            'recipients' => $this->stakeholderEmployeeIds($task),
            'title' => 'Task Completed',
            'body' => $body,
            'data' => $data,
        ];
    }

    private function buildUpdatedPerforms(Task $task, int $actorEid, array $validated): array
    {
        $result = $this->latestResult($task);
        $actorName = $this->employeeName($actorEid) ?? 'A supervisor';

        $data = $this->buildTaskData($task, $actorEid, self::TYPE_UPDATED_PERFORMS);
        $data['result_id'] = $result ? (int) $result->id : null;
        $data['performed_by'] = $result && $result->executed_by ? (int) $result->executed_by : $actorEid;
        $data['performed_by_name'] = $result && $result->executed_by
            ? $this->employeeName((int) $result->executed_by)
            : $actorName;
        $data['performed_at'] = $result && $result->executed_date
            ? $result->executed_date->format('Y-m-d')
            : ($task->updated_at ? $task->updated_at->toDateTimeString() : null);
        $data['reported_status'] = $result ? $result->current_status : $task->status;
        $data['progress'] = [
            'checklist_total' => $data['checklist_total'],
            'checklist_completed' => $data['checklist_completed'],
            'checklist_progress_percent' => $data['checklist_progress_percent'],
            'status' => $task->status,
        ];
        $data['trigger'] = 'performs_updated';

        return [
            'recipients' => $this->stakeholderEmployeeIds($task),
            'title' => 'Task Progress Updated',
            'body' => 'The progress of the task "' . $task->task_name . '" (#' . $task->id . ') was updated by ' . $actorName . '. Checklist items completed: ' . $data['checklist_completed'] . ' of ' . $data['checklist_total'] . '. Current status: ' . $task->status . '.',
            'data' => $data,
        ];
    }

    /**
     * Fields shared by every task notification payload. Only columns that
     * exist on tasks, tasks_assignees and tasks_checklists are used.
     */
    private function buildTaskData(Task $task, int $actorEid, string $action): array
    {
        $progress = $this->checklistProgress($task);

        return array_merge([
            'action' => $action,
            'module' => 'tasks',
            'task_id' => (int) $task->id,
            'task_name' => $task->task_name,
            'description' => $task->notes ? Str::limit($task->notes, 500) : null,
            'status' => $task->status,
            'priority' => $task->priority,
            'bucket' => $task->bucket,
            'privacy' => $task->privacy,
            'repeat' => $task->repeat,
            'start_date' => $task->start_date ? $task->start_date->format('Y-m-d') : null,
            'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
            'new_due_date' => $task->new_due_date ? $task->new_due_date->format('Y-m-d') : null,
            'created_by' => (int) $task->created_by,
            'created_by_name' => $this->employeeName((int) $task->created_by),
            'created_at' => $task->created_at ? $task->created_at->toDateTimeString() : null,
            'updated_at' => $task->updated_at ? $task->updated_at->toDateTimeString() : null,
            'assigned_to' => $this->assigneeIds($task),
            'assigned_to_names' => $this->assigneeNames($task),
            'assigned_by' => (int) $task->created_by,
            'assigned_by_name' => $this->employeeName((int) $task->created_by),
            'updated_by' => $task->updated_by ? (int) $task->updated_by : null,
            'updated_by_name' => $task->updated_by ? $this->employeeName((int) $task->updated_by) : null,
            'actor_employee_id' => $actorEid,
            'actor_name' => $this->employeeName($actorEid),
            'assignees' => $this->assigneeSummary($task),
        ], $progress);
    }

    /**
     * @return array<int, int>
     */
    private function assigneeIds(Task $task): array
    {
        return $task->assignees
            ->map(fn(TasksAssignee $assignee) => (int) $assignee->assigned_to)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function assigneeNames(Task $task): array
    {
        $names = [];

        foreach ($this->assigneeIds($task) as $employeeId) {
            $name = $this->employeeName($employeeId);

            if ($name) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Assignees plus the task creator, mirroring the "assignee or creator"
     * stakeholder rule already used by the planner task access check.
     */
    private function stakeholderEmployeeIds(Task $task): array
    {
        return array_values(array_unique(array_merge($this->assigneeIds($task), [(int) $task->created_by])));
    }

    private function assigneeSummary(Task $task): array
    {
        return $task->assignees->map(function (TasksAssignee $assignee) {
            $employeeId = $assignee->assigned_to ? (int) $assignee->assigned_to : 0;
            $employee = $this->employeeDirectory($employeeId);

            return [
                'assignee_id' => (int) $assignee->id,
                'employee_id' => $employeeId ?: null,
                'employee_name' => $employee ? $employee->Full_Name : null,
                'employee_code' => $employee ? $employee->Employee_Id : null,
                'assigned_by' => (int) $assignee->assigned_by,
                'assigned_by_name' => $this->employeeName((int) $assignee->assigned_by),
                'read_status' => (bool) $assignee->read_status,
            ];
        })->values()->all();
    }

    /**
     * @return array{checklist_total: int, checklist_completed: int, checklist_progress_percent: int}
     */
    private function checklistProgress(Task $task): array
    {
        $total = $task->checklists->count();
        $completed = $task->checklists->where('completed_status', true)->count();

        return [
            'checklist_total' => $total,
            'checklist_completed' => $completed,
            'checklist_progress_percent' => $total > 0 ? (int) round($completed / $total * 100) : 0,
        ];
    }

    private function resolveChecklist(Task $task, ?int $checklistId): TasksChecklists
    {
        if ($checklistId) {
            $checklist = TasksChecklists::where('task_id', $task->id)->find($checklistId);

            if (! $checklist) {
                throw ValidationException::withMessages([
                    'checklist_id' => 'The checklist item does not belong to this task.',
                ]);
            }

            return $checklist;
        }

        $checklist = TasksChecklists::where('task_id', $task->id)->orderByDesc('id')->first();

        if (! $checklist) {
            throw ValidationException::withMessages([
                'checklist_id' => 'This task has no checklist item yet.',
            ]);
        }

        return $checklist;
    }

    private function latestResult(Task $task): ?TasksResult
    {
        return $task->results->sortByDesc('id')->first();
    }

    private function resolveCompletedAt(Task $task, ?TasksResult $result): ?string
    {
        if ($result && $result->current_status === Task::STATUS_COMPLETED && $result->executed_date) {
            return $result->executed_date->toDateTimeString();
        }

        return $task->updated_at ? $task->updated_at->toDateTimeString() : null;
    }

    private function employeeDirectory(int $employeeId): ?employee
    {
        if ($employeeId <= 0) {
            return null;
        }

        if (! array_key_exists($employeeId, $this->employeeDirectory)) {
            $this->employeeDirectory[$employeeId] = employee::select('id', 'Full_Name', 'Employee_Id')
                ->find($employeeId);
        }

        return $this->employeeDirectory[$employeeId];
    }

    private function employeeName(int $employeeId): ?string
    {
        $employee = $this->employeeDirectory($employeeId);

        return $employee ? $employee->Full_Name : null;
    }

    /**
     * Employee id of the authenticated user, using the existing auth guard.
     */
    private function currentEmployeeId(): int
    {
        $user = Auth::user();

        return $user ? (int) $user->EID : 0;
    }

    private function joinNames(array $names): string
    {
        $names = array_values(array_filter($names));

        return $names ? implode(', ', $names) : 'N/A';
    }

    private function formatTimestamp($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function encodeJson(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
