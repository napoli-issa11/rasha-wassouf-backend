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
    public ?string $intendedRecipient;
    public bool $isDemoMode;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $clientName,
        string $emailSubject,
        string $replyMessage,
        ?string $projectCategory = null,
        ?string $intendedRecipient = null,
        bool $isDemoMode = false
    ) {
        $this->clientName = $clientName;
        $this->emailSubject = $emailSubject;
        $this->replyMessage = $replyMessage;
        $this->projectCategory = $projectCategory;
        $this->intendedRecipient = $intendedRecipient;
        $this->isDemoMode = $isDemoMode;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $replyTo = ($this->isDemoMode && !empty($this->intendedRecipient))
            ? $this->intendedRecipient
            : (config('mail.admin_address') ?: config('mail.from.address'));

        $subject = $this->isDemoMode
            ? '[Demo Sandbox] ' . $this->emailSubject
            : $this->emailSubject;

        return new Envelope(
            subject: $subject,
            replyTo: $replyTo ? [$replyTo] : [],
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
                'intendedRecipient' => $this->intendedRecipient,
                'isDemoMode' => $this->isDemoMode,
            ],
        );
    }
}
