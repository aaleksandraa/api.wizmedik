<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TerminOtkazan extends Mailable
{
    use Queueable, SerializesModels;

    public $termin;
    public $recipientType; // 'doctor', 'clinic', 'patient'
    public $cancelledBy;   // 'patient', 'doctor', 'clinic'

    public function __construct($termin, $recipientType = 'patient', $cancelledBy = 'patient')
    {
        $this->termin = $termin;
        $this->recipientType = $recipientType;
        $this->cancelledBy = $cancelledBy;
    }

    public function envelope(): Envelope
    {
        $subjects = [
            'doctor' => 'Termin otkazan - WizMedik',
            'clinic' => 'Termin otkazan u vašoj klinici - WizMedik',
            'patient' => 'Vaš termin je otkazan - WizMedik',
        ];

        // Set Reply-To based on who should receive replies
        $replyTo = $this->getReplyToAddress();

        return new Envelope(
            subject: $subjects[$this->recipientType] ?? 'Termin otkazan - WizMedik',
            replyTo: $replyTo ? [new Address($replyTo['email'], $replyTo['name'])] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.termin-otkazan',
        );
    }

    /**
     * Get Reply-To address based on recipient type
     */
    private function getReplyToAddress(): ?array
    {
        $doktor = $this->termin->doktor;
        $klinika = $doktor?->klinika;

        // If sending to patient, reply should go to doctor or clinic
        if ($this->recipientType === 'patient') {
            if ($klinika && $klinika->contact_email) {
                return [
                    'email' => $klinika->contact_email,
                    'name' => $klinika->naziv,
                ];
            }
            $doctorEmail = $doktor?->user?->email ?? $doktor?->email;
            if ($doktor && $doctorEmail) {
                return [
                    'email' => $doctorEmail,
                    'name' => "Dr. {$doktor->ime} {$doktor->prezime}",
                ];
            }
        }

        // If sending to doctor, reply should go to patient (if registered) or clinic
        if ($this->recipientType === 'doctor') {
            if ($this->termin->user && $this->termin->user->email) {
                return [
                    'email' => $this->termin->user->email,
                    'name' => "{$this->termin->user->ime} {$this->termin->user->prezime}",
                ];
            }
            if ($this->termin->guest_email) {
                return [
                    'email' => $this->termin->guest_email,
                    'name' => "{$this->termin->guest_ime} {$this->termin->guest_prezime}",
                ];
            }
        }

        // If sending to clinic, reply should go to patient
        if ($this->recipientType === 'clinic') {
            if ($this->termin->user && $this->termin->user->email) {
                return [
                    'email' => $this->termin->user->email,
                    'name' => "{$this->termin->user->ime} {$this->termin->user->prezime}",
                ];
            }
            if ($this->termin->guest_email) {
                return [
                    'email' => $this->termin->guest_email,
                    'name' => "{$this->termin->guest_ime} {$this->termin->guest_prezime}",
                ];
            }
        }

        return null;
    }
}
