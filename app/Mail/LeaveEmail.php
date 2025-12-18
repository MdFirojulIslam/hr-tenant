<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $from_date;
    public $to_date;
    public $purpose;
    public $leave_type;
    public $name;
    public $eid;
    public $designation;

    /**
     * Create a new message instance.
     */
    public function __construct($from_date, $to_date, $purpose, $leave_type, $name, $eid, $designation)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->purpose = $purpose;
        $this->leave_type = $leave_type;
        $this->name = $name;
        $this->eid = $eid;
        $this->designation = $designation;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Leave Request - ' . $this->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.leave_email',
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
