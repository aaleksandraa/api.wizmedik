<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAccessInvitationNotification extends Notification
{
    public function __construct(
        private readonly string $token,
        private readonly string $profileType,
        private readonly ?string $profileName = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $url = $frontendUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode((string) $notifiable->email);

        return (new MailMessage)
            ->subject('Pozivnica za pristup panelu - WizMedik')
            ->view('emails.admin-access-invitation', [
                'url' => $url,
                'email' => $notifiable->email,
                'profileType' => $this->profileType,
                'profileName' => $this->profileName,
            ]);
    }
}
