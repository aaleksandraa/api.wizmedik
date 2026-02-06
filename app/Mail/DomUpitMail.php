<?php

namespace App\Mail;

use App\Models\DomUpit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DomUpitMail extends Mailable
{
    use Queueable, SerializesModels;

    public $upit;
    public $dom;

    /**
     * Create a new message instance.
     */
    public function __construct(DomUpit $upit)
    {
        $this->upit = $upit;
        $this->dom = $upit->dom;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Novi upit za ' . $this->dom->naziv,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.dom-upit',
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
