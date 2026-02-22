<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class ResetPasswordNotification extends Notification
{
    public $token;

    public function __construct($token)
    {
        $this->token = $token;

        Log::info('ResetPasswordNotification created (direct mail)', [
            'token_length' => strlen($token),
            'system' => 'Direct Mail (no queue)'
        ]);
    }

    public function via($notifiable): array
    {
        Log::info('ResetPasswordNotification via() called', [
            'email' => $notifiable->email,
            'channels' => ['mail']
        ]);

        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        Log::info('Sending password reset email directly', [
            'email' => $notifiable->email,
            'system' => 'Direct Mail (no queue)'
        ]);

        $frontendUrl = config('app.frontend_url');
        $url = $frontendUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Resetovanje lozinke - WizMedik')
            ->view('emails.reset-lozinke', [
                'token' => $this->token,
                'email' => $notifiable->email,
                'url' => $url,
            ]);
    }
}
