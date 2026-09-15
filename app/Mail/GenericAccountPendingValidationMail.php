<?php

namespace App\Mail;

use App\Models\GenericAccount;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericAccountPendingValidationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GenericAccount $account,
        public User $recipient,
        public User $sender,
    ) {
        $this->account->loadMissing(['environment', 'creator']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Compte générique à valider — '.$this->account->user_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.gouvernance.generic-account-pending',
            with: [
                'account' => $this->account,
                'recipient' => $this->recipient,
                'sender' => $this->sender,
                'appUrl' => config('app.url'),
            ],
        );
    }
}
