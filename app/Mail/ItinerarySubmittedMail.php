<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ItinerarySubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */

    public string $senderName;
    public string $senderDesignation;
    public string $itineraryFor;
    public function __construct($senderName, $senderDesignation, $itineraryFor)
    {
        $this->senderName = $senderName;
        $this->senderDesignation = $senderDesignation;
        $this->itineraryFor = $itineraryFor;
    }
    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Itinerary Submitted Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.itinerary_send',
            with: [
                'senderName' => $this->senderName,
                'senderDesignation' => $this->senderDesignation,
                'itineraryFor' => $this->itineraryFor,
            ]
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
