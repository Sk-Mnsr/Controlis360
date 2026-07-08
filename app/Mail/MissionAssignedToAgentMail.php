<?php

namespace App\Mail;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MissionAssignedToAgentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Mission $mission,
        public User $agent,
        public User $responsable,
    ) {
        $this->mission->loadMissing(['recommendations', 'entities']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mission à traiter — '.$this->mission->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.missions.assigned-to-agent',
            with: [
                'mission' => $this->mission,
                'agent' => $this->agent,
                'responsable' => $this->responsable,
                'appUrl' => config('app.url'),
            ],
        );
    }
}
