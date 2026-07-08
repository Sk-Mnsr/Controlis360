<?php

namespace App\Mail;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MissionAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Mission $mission,
        public User $recipient,
        public User $sender,
    ) {
        $this->mission->loadMissing(['recommendations', 'entities']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle mission — '.$this->mission->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.missions.assigned',
            with: [
                'mission' => $this->mission,
                'recipient' => $this->recipient,
                'sender' => $this->sender,
                'appUrl' => config('app.url'),
            ],
        );
    }
}
