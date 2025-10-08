<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveStatusEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $from_date;
    public $to_date;
    public $leave_type;
    public $name;
    public $status;

    public function __construct($from_date, $to_date, $status, $leave_type, $name)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->status = $status;
        $this->leave_type = $leave_type;
        $this->name = $name;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Leave Status - '.$this->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.leave_status_email',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
