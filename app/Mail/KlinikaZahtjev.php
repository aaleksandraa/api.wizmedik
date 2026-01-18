<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KlinikaZahtjev extends Mailable
{
    use Queueable, SerializesModels;

    public $zahtjev;
    public $klinika;
    public $doktor;
    public $type; // 'doctor_request' or 'clinic_invitation'

    public function __construct($zahtjev, $klinika, $doktor, $type)
    {
        $this->zahtjev = $zahtjev;
        $this->klinika = $klinika;
        $this->doktor = $doktor;
        $this->type = $type;
    }

    public function envelope(): Envelope
    {
        $subject = $this->type === 'doctor_request'
            ? 'Novi zahtjev za pridruživanje - ' . $this->doktor->ime . ' ' . $this->doktor->prezime
            : 'Poziv za pridruživanje klinici - ' . $this->klinika->naziv;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.klinika-zahtjev',
        );
    }
}
