<?php

namespace App\Mail;

use App\Models\BanjaUpit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BanjaUpitMail extends Mailable
{
    use Queueable, SerializesModels;

    public $upit;
    public $banja;

    /**
     * Create a new message instance.
     */
    public function __construct(BanjaUpit $upit)
    {
        $this->upit = $upit;
        $this->banja = $upit->banja;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Novi upit za ' . $this->banja->naziv,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.banja-upit',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
