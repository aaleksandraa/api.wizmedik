<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class EmailVerificationNotification extends Notification
{
    public function __construct()
    {
        Log::info('EmailVerificationNotification created (direct mail)', [
            'system' => 'Direct Mail (no queue)'
        ]);
    }

    public function via($notifiable): array
    {
        Log::info('EmailVerificationNotification via() called', [
            'email' => $notifiable->email,
            'channels' => ['mail']
        ]);

        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        Log::info('Sending email verification directly', [
            'email' => $notifiable->email,
            'system' => 'Direct Mail (no queue)'
        ]);

        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifikacija email adrese - WizMedik')
            ->view('emails.verify-email', [
                'user' => $notifiable,
                'verificationUrl' => $verificationUrl,
            ]);
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl($notifiable)
    {
        $frontendUrl = env('APP_FRONTEND_URL', 'https://wizmedik.com');

        $url = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        // Convert backend URL to frontend URL
        $backendUrl = env('APP_URL', 'https://api.wizmedik.com');
        $verificationUrl = str_replace($backendUrl, $frontendUrl, $url);

        return $verificationUrl . '&redirect=verify-email';
    }
}
