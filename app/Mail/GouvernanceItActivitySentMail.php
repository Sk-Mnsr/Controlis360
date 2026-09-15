<?php

namespace App\Mail;

use App\Models\GouvernanceItActivity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GouvernanceItActivitySentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GouvernanceItActivity $activity,
        public User $recipient,
        public User $sender,
    ) {
        $this->activity->loadMissing(['environment', 'creator']);
    }

    public function envelope(): Envelope
    {
        $title = trim((string) $this->activity->title);

        return new Envelope(
            subject: 'Nouvelle activité GovStrat envoyée — '.($title !== '' ? $title : '#'.$this->activity->id),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.gouvernance.activity-sent',
            with: [
                'activity' => $this->activity,
                'recipient' => $this->recipient,
                'sender' => $this->sender,
                'appUrl' => config('app.url'),
                'activityUrl' => $this->activityUrl(),
                'sectionLabel' => GouvernanceItActivity::SECTIONS[$this->activity->section] ?? $this->activity->section,
                'moduleLabel' => match ($this->activity->module_slug) {
                    'centre_support' => 'Centre Support',
                    'systemes_reseaux' => 'Systèmes & Réseaux',
                    'base_donnees' => 'Base de données',
                    default => (string) $this->activity->module_slug,
                },
            ],
        );
    }

    private function activityUrl(): string
    {
        $slug = match ($this->activity->module_slug) {
            'centre_support' => 'centre-support',
            'systemes_reseaux' => 'systemes-reseaux',
            'base_donnees' => 'base-donnees',
            default => 'centre-support',
        };

        return rtrim((string) config('app.url'), '/').'/gouvernance-it/'.$slug;
    }
}
