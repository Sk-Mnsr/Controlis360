<?php

namespace App\Mail;

use App\Models\Mission;
use App\Models\Recommendation;
use App\Models\RecommendationReturn;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecommendationReturnedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Mission $mission,
        public Recommendation $recommendation,
        public RecommendationReturn $return,
        public User $recipient,
        public User $sender,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recommandation retournée — '.$this->recommendation->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.missions.recommendation-returned',
            with: [
                'mission' => $this->mission,
                'recommendation' => $this->recommendation,
                'return' => $this->return,
                'recipient' => $this->recipient,
                'sender' => $this->sender,
                'appUrl' => config('app.url'),
            ],
        );
    }
}
