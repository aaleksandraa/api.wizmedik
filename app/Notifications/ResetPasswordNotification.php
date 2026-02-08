<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 60;
    public $maxExceptions = 2;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
        $this->onQueue('high'); // Use high priority queue for password resets - optimized for Horizon
        $this->delay(now()->addSeconds(1)); // Minimal delay for Horizon performance
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        Log::info('Sending password reset email via Horizon', [
            'email' => $notifiable->email,
            'queue' => 'high',
            'system' => 'Laravel Horizon'
        ]);

        $frontendUrl = env('APP_FRONTEND_URL', 'http://localhost:5173');
        $url = $frontendUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Resetovanje lozinke - WizMedik')
            ->view('emails.reset-lozinke', [
                'token' => $this->token,
                'email' => $notifiable->email,
            ]);
    }

    public function failed($exception)
    {
        Log::error('Password reset email failed in Horizon', [
            'token' => $this->token,
            'error' => $exception->getMessage(),
            'system' => 'Laravel Horizon'
        ]);
    }
}
