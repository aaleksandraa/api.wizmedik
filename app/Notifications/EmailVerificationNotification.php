<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class EmailVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $maxExceptions = 2;

    public function __construct()
    {
        $this->onQueue('high'); // Use high priority queue for email verification
        $this->delay(now()->addSeconds(1)); // Minimal delay for Horizon performance

        Log::info('EmailVerificationNotification created', [
            'queue' => 'high',
            'system' => 'Laravel Horizon'
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
        Log::info('Sending email verification via Horizon', [
            'email' => $notifiable->email,
            'queue' => 'high',
            'system' => 'Laravel Horizon'
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

    public function failed($exception)
    {
        Log::error('Email verification failed in Horizon', [
            'error' => $exception->getMessage(),
            'system' => 'Laravel Horizon'
        ]);
    }
}
