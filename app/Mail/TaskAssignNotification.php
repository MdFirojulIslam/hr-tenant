<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Task Assignment Notification Mailable.
 *
 * Used to deliver task assignment emails to the assigned employees.
 *
 * The email body uses the existing template:
 * resources/views/emails/task_assign.blade.php
 *
 * System sender: noreply@hr.artisanbn.com (from MAIL_FROM_ADDRESS env)
 * Recipient: employees.Official_mail of the assigned employee
 */
class TaskAssignNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Existing Blade template used as the email body.
     */
    public const VIEW = 'emails.task_assign';

    /**
     * Production delivery is enabled.
     * This constant is informational; actual delivery is controlled by the caller
     * (e.g., TasksMailController::mailTaskAssignees).
     */
    public const DELIVERY_ENABLED = true;

    /** employees.id of the assigned employee. */
    public int $employeeId;

    /**
     * Email of the assigned employee, resolved from employees.Official_mail.
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
     * @param  string  $recipientEmail  employees.Official_mail of the assigned employee.
     * @param  string  $recipientName  Full name of the assigned employee.
     * @param  string  $taskName  Task name.
     * @param  int  $taskId  Task id.
     * @param  string  $assignedByName  Name of the person who assigned the task (from employees table).
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
     *
     * The system sender (noreply@hr.artisanbn.com) is configured in MAIL_FROM_ADDRESS env.
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
}