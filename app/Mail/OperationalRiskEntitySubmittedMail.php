<?php

namespace App\Mail;

use App\Models\OperationalRiskRow;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OperationalRiskEntitySubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OperationalRiskRow $row,
        public User $recipient,
        public User $sender,
    ) {
        $this->row->loadMissing(['entity', 'assignedEntity']);
    }

    public function envelope(): Envelope
    {
        $name = trim((string) $this->row->sub_process_name);

        return new Envelope(
            subject: 'Complétion entité reçue — '.($name !== '' ? $name : '#'.$this->row->id),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.cartographie.risk-entity-submitted',
            with: [
                'row' => $this->row,
                'recipient' => $this->recipient,
                'sender' => $this->sender,
                'appUrl' => config('app.url'),
                'riskUrl' => $this->riskUrl(),
            ],
        );
    }

    private function riskUrl(): string
    {
        $code = $this->row->entity?->code;

        if ($code) {
            return rtrim((string) config('app.url'), '/').'/cartographie/departements/'.$code;
        }

        return rtrim((string) config('app.url'), '/').'/cartographie/plus-gros-risques';
    }
}
