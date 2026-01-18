<?php

namespace App\Mail;

use App\Models\OdgovorNaPitanje;
use App\Models\Pitanje;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OdgovorNaPitanjeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pitanje;
    public $odgovor;
    public $doktorIme;

    public function __construct(Pitanje $pitanje, OdgovorNaPitanje $odgovor)
    {
        $this->pitanje = $pitanje;
        $this->odgovor = $odgovor;
        $this->doktorIme = "Dr. {$odgovor->doktor->ime} {$odgovor->doktor->prezime}";
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Dobili ste odgovor na vaše pitanje: {$this->pitanje->naslov}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.odgovor-na-pitanje',
        );
    }
}
