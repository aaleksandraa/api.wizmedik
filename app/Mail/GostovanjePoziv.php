<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GostovanjePoziv extends Mailable
{
    use Queueable, SerializesModels;

    public $gostovanje;
    public $klinika;
    public $doktor;

    public function __construct($gostovanje, $klinika, $doktor)
    {
        $this->gostovanje = $gostovanje;
        $this->klinika = $klinika;
        $this->doktor = $doktor;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Poziv za gostovanje - ' . $this->klinika->naziv,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gostovanje-poziv',
        );
    }
}
