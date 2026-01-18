<?php

namespace App\Mail;

use App\Models\RegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $registrationRequest;
    public $isFree;

    public function __construct(RegistrationRequest $registrationRequest)
    {
        $this->registrationRequest = $registrationRequest;
        $key = $registrationRequest->type . '_registration_free';
        $this->isFree = \App\Models\SiteSetting::get($key, 'true') === 'true';
    }

    public function build()
    {
        return $this->subject('Zahtjev za registraciju primljen - wizMedik')
                    ->view('emails.registration-received');
    }
}
