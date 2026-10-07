<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EncheresCommitteeCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public string $lotTitle,
        public string $lotReference,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre code personnel — '.$this->lotReference,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.encheres.committee-code',
            with: [
                'recipient' => $this->recipient,
                'lotTitle' => $this->lotTitle,
                'lotReference' => $this->lotReference,
                'code' => $this->code,
                'appUrl' => rtrim((string) config('app.url'), '/'),
            ],
        );
    }
};
