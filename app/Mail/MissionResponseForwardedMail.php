<?php

namespace App\Mail;

use App\Models\Mission;
use App\Models\MissionResponse;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MissionResponseForwardedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Mission $mission,
        public MissionResponse $response,
        public User $recipient,
        public User $sender,
    ) {
        $this->mission->loadMissing(['recommendations', 'entities', 'creator']);
    }

    public function envelope(): Envelope
    {
        $type = $this->response->response_type_fr ?? $this->response->response_type;

        return new Envelope(
            subject: "Réponse mission {$this->mission->reference} — {$type}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.missions.response-forwarded',
            with: [
                'mission' => $this->mission,
                'response' => $this->response,
                'recipient' => $this->recipient,
                'sender' => $this->sender,
                'appUrl' => config('app.url'),
            ],
        );
    }
}
