<?php

namespace App\Mail;

use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $registrationRequest;
    public $user;
    public $loginUrl;

    public function __construct(RegistrationRequest $registrationRequest, User $user)
    {
        $this->registrationRequest = $registrationRequest;
        $this->user = $user;
        $this->loginUrl = config('app.frontend_url') . '/login';
    }

    public function build()
    {
        return $this->subject('Vaš zahtjev je odobren - wizMedik')
                    ->view('emails.registration-approved');
    }
}
