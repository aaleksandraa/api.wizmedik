<?php

namespace App\Mail;

use App\Models\RegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $registrationRequest;
    public $contactEmail;

    public function __construct(RegistrationRequest $registrationRequest)
    {
        $this->registrationRequest = $registrationRequest;
        $this->contactEmail = \App\Models\SiteSetting::get('registration_admin_email', 'info@wizmedik.ba');
    }

    public function build()
    {
        return $this->subject('Vaš zahtjev nije odobren - wizMedik')
                    ->view('emails.registration-rejected');
    }
}
