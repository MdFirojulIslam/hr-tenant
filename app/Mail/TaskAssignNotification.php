<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Factory as Queue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Prepared representation of the "task assigned" notification that belongs to an
 * employee once they are added to a task from the planner assignee picker
 * (resources/js/pages/planner/components/TaskAssigneeModal.vue).
 *
 * Intended relationship:
 *   Task Assignment -> Assigned Employee -> Employee Email -> emails.task_assign
 *
 * The email body is the existing template
 * resources/views/emails/task_assign.blade.php. No second template was added.
 *
 * DELIVERY IS CURRENTLY DISABLED.
 *
 * This class deliberately does not implement ShouldQueue and it overrides every
 * public delivery entry point of the base Mailable (send, queue, later), so the
 * notification can neither be delivered nor enqueued regardless of the caller:
 *
 *   Mail::to($x)->send(new TaskAssignNotification(...))    -> blocked by send()
 *   Mail::to($x)->queue(new TaskAssignNotification(...))  -> blocked by queue()
 *   Mail::to($x)->later(60, new TaskAssignNotification())  -> blocked by later()
 *   Mail::queue(new TaskAssignNotification(...))           -> blocked by queue()
 *   SendQueuedMailable                                     -> can only be built by
 *                                                             the blocked methods
 *
 * The instance may be created and inspected (recipient, subject, view, view
 * data). Enabling delivery later is a single deliberate step: set
 * DELIVERY_ENABLED to true and remove the send()/queue()/later() overrides.
 */
class TaskAssignNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Existing Blade template used as the email body.
     */
    public const VIEW = 'emails.task_assign';

    /**
     * Global delivery kill switch for this mailable.
     *
     * While this is false the overridden send()/queue()/later() methods return
     * immediately without touching the mail transport or the mail queue.
     */
    public const DELIVERY_ENABLED = false;

    /** employees.id of the assigned employee. */
    public int $employeeId;

    /**
     * Email of the assigned employee, resolved from users.email through
     * users.EID = employees.id.
     */
    public string $recipientEmail;

    public string $recipientName;

    public string $taskName;

    public int $taskId;

    public string $assignedByName;

    public string $status;

    public string $priority;

    public string $startDate;

    public string $dueDate;

    public string $assignedAt;

    public string $actionUrl;

    /**
     * @param  int  $employeeId  employees.id of the assigned employee.
     * @param  string  $recipientEmail  Email of the assigned employee.
     * @param  string  $recipientName  Full name of the assigned employee.
     * @param  string  $taskName  Task name.
     * @param  int  $taskId  Task id.
     * @param  string  $assignedByName  Name of the person who assigned the task.
     * @param  string  $status  Current task status.
     * @param  string  $priority  Task priority.
     * @param  string  $startDate  Task start date (Y-m-d).
     * @param  string  $dueDate  Task due date (Y-m-d).
     * @param  string  $assignedAt  Assignment timestamp (Y-m-d H:i:s).
     * @param  string  $actionUrl  Absolute task URL used by the call to action.
     */
    public function __construct(
        int $employeeId,
        string $recipientEmail,
        string $recipientName,
        string $taskName,
        int $taskId,
        string $assignedByName = '',
        string $status = '',
        string $priority = '',
        string $startDate = '',
        string $dueDate = '',
        string $assignedAt = '',
        string $actionUrl = '',
    ) {
        $this->employeeId = $employeeId;
        $this->recipientEmail = $recipientEmail;
        $this->recipientName = $recipientName;
        $this->taskName = $taskName;
        $this->taskId = $taskId;
        $this->assignedByName = $assignedByName;
        $this->status = $status;
        $this->priority = $priority;
        $this->startDate = $startDate;
        $this->dueDate = $dueDate;
        $this->assignedAt = $assignedAt;
        $this->actionUrl = $actionUrl;
    }

    /**
     * The recipient is the assigned employee, never the person who performed
     * the assignment.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->hasRecipientEmail()
                ? [new Address($this->recipientEmail, $this->recipientName)]
                : [],
            subject: $this->subjectLine(),
        );
    }

    /**
     * The existing task assignment email template.
     */
    public function content(): Content
    {
        return new Content(
            view: self::VIEW,
            with: $this->viewData(),
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * True when the assigned employee has an email address that could be used
     * as the recipient once delivery is switched on.
     */
    public function hasRecipientEmail(): bool
    {
        return filter_var($this->recipientEmail, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function subjectLine(): string
    {
        return 'You have been assigned to task '.$this->taskName.' (#'.$this->taskId.')';
    }

    /**
     * View data handed to emails.task_assign.blade.php.
     *
     * @return array<string, string>
     */
    public function viewData(): array
    {
        return [
            'assignee_name' => $this->recipientName,
            'assignee_email' => $this->recipientEmail,
            'task_name' => $this->taskName,
            'task_id' => (string) $this->taskId,
            'assigned_by_name' => $this->assignedByName,
            'status' => $this->status,
            'priority' => $this->priority,
            'start_date' => $this->startDate,
            'due_date' => $this->dueDate,
            'assigned_at' => $this->assignedAt,
            'action_url' => $this->actionUrl,
            'notification_type' => 'task update',
        ];
    }

    /**
     * Delivery is blocked. This is the entry point every Mailable goes through
     * for Mailer::send() and PendingMail::send().
     *
     * @param  \Illuminate\Contracts\Mail\Factory|\Illuminate\Contracts\Mail\Mailer  $mailer
     */
    public function send($mailer)
    {
        $this->reportBlockedDelivery('send');
    }

    /**
     * Queueing is blocked. Entry point for Mailer::queue() and
     * PendingMail::queue().
     */
    public function queue(Queue $queue)
    {
        $this->reportBlockedDelivery('queue');
    }

    /**
     * Delayed queueing is blocked. Entry point for Mailer::later() and
     * PendingMail::later().
     */
    public function later($delay, Queue $queue)
    {
        $this->reportBlockedDelivery('later');
    }

    /**
     * Audit trail for the deliberate "prepared, not delivered" state.
     *
     * This records the blocked attempt only. It never touches the mail
     * transport, the mail queue, a Mailable send or any recipient.
     */
    private function reportBlockedDelivery(string $entryPoint): void
    {
        Log::warning('Task assignment notification prepared but not delivered', [
            'entry_point' => $entryPoint,
            'delivery_enabled' => self::DELIVERY_ENABLED,
            'view' => self::VIEW,
            'task_id' => $this->taskId,
            'assignee_employee_id' => $this->employeeId,
            'assignee_name' => $this->recipientName,
            'recipient_email_resolved' => $this->hasRecipientEmail(),
        ]);
    }
}