<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetByAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public string $plainPassword,
        public ?User $sender = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Réinitialisation de votre mot de passe',
        );
    }

    public function content(): Content
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        return new Content(
            html: 'emails.users.password-reset',
            with: [
                'recipient' => $this->recipient,
                'plainPassword' => $this->plainPassword,
                'sender' => $this->sender,
                'loginUrl' => $appUrl.'/login',
                'logoUrl' => $appUrl.'/logo_Cofina.png',
            ],
        );
    }
}
