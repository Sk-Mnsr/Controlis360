<?php

namespace App\Mail;

use App\Models\GenericAccount;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericAccountValidatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GenericAccount $account,
        public User $recipient,
        public User $validator,
    ) {
        $this->account->loadMissing(['environment', 'validator']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Compte générique validé — '.$this->account->user_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.gouvernance.generic-account-validated',
            with: [
                'account' => $this->account,
                'recipient' => $this->recipient,
                'validator' => $this->validator,
                'appUrl' => config('app.url'),
            ],
        );
    }
}
