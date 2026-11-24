<?php

namespace App\Mail;

use App\Models\PciDocument;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PciDocumentPendingValidationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PciDocument $document,
        public User $recipient,
        public User $sender,
        public string $action = 'update',
    ) {
        $this->document->loadMissing('environment');
    }

    public function envelope(): Envelope
    {
        $subject = $this->action === 'deletion'
            ? 'Retrait à valider — '.$this->document->reference
            : 'Document à valider — '.$this->document->reference;

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.gouvernance.pci-document-pending',
            with: [
                'document' => $this->document,
                'recipient' => $this->recipient,
                'sender' => $this->sender,
                'appUrl' => config('app.url'),
            ],
        );
    }
}
