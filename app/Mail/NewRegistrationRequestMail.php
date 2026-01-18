<?php

namespace App\Mail;

use App\Models\RegistrationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewRegistrationRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $registrationRequest;
    public $adminUrl;

    public function __construct(RegistrationRequest $registrationRequest)
    {
        $this->registrationRequest = $registrationRequest;
        $this->adminUrl = config('app.frontend_url') . '/admin/registration-requests/' . $registrationRequest->id;
    }

    public function build()
    {
        $typeName = $this->registrationRequest->type === 'doctor' ? 'Doktor' : 'Klinika';

        return $this->subject("Novi zahtjev za registraciju - {$typeName} - wizMedik")
                    ->view('emails.new-registration-request');
    }
}
