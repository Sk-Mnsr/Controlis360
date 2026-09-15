<?php

namespace App\Mail;

use App\Models\OperationalRiskRow;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OperationalRiskRevisionRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OperationalRiskRow $row,
        public User $recipient,
        public User $requester,
        public string $comment,
    ) {
        $this->row->loadMissing(['entity']);
    }

    public function envelope(): Envelope
    {
        $name = trim((string) $this->row->sub_process_name);

        return new Envelope(
            subject: 'Révision demandée — '.($name !== '' ? $name : '#'.$this->row->id),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.cartographie.risk-revision',
            with: [
                'row' => $this->row,
                'recipient' => $this->recipient,
                'requester' => $this->requester,
                'comment' => $this->comment,
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

        return rtrim((string) config('app.url'), '/').'/cartographie/saisie-risques';
    }
}
