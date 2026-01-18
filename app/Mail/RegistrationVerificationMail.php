<?php

namespace App\Mail;

use App\Models\RegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $registrationRequest;
    public $verificationUrl;

    public function __construct(RegistrationRequest $registrationRequest)
    {
        $this->registrationRequest = $registrationRequest;
        $this->verificationUrl = config('app.frontend_url') . '/register/verify/' . $registrationRequest->email_verification_token;
    }

    public function build()
    {
        return $this->subject('Verifikujte vašu email adresu - wizMedik')
                    ->view('emails.registration-verification');
    }
}
