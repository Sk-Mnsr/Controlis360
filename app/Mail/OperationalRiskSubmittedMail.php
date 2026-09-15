<?php

namespace App\Mail;

use App\Models\OperationalRiskRow;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OperationalRiskSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OperationalRiskRow $row,
        public User $recipient,
        public User $sender,
    ) {
        $this->row->loadMissing(['entity', 'createdBy']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Risque opérationnel à valider — '.$this->rowLabel(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.cartographie.risk-submitted',
            with: [
                'row' => $this->row,
                'recipient' => $this->recipient,
                'sender' => $this->sender,
                'appUrl' => config('app.url'),
                'riskUrl' => $this->riskUrl(),
            ],
        );
    }

    private function rowLabel(): string
    {
        $name = trim((string) $this->row->sub_process_name);

        return $name !== '' ? $name : '#'.$this->row->id;
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
