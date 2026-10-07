<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EncheresClientOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre code EnchèreSN',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.encheres.client-otp',
            with: [
                'email' => $this->email,
                'code' => $this->code,
            ],
        );
    }
}
