<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReplyToClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $clientName;
    public string $emailSubject;
    public string $replyMessage;
    public ?string $projectCategory;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $clientName,
        string $emailSubject,
        string $replyMessage,
        ?string $projectCategory = null
    ) {
        $this->clientName = $clientName;
        $this->emailSubject = $emailSubject;
        $this->replyMessage = $replyMessage;
        $this->projectCategory = $projectCategory;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reply-to-client',
            with: [
                'clientName' => $this->clientName,
                'emailSubject' => $this->emailSubject,
                'replyMessage' => $this->replyMessage,
                'projectCategory' => $this->projectCategory,
            ],
        );
    }
}
