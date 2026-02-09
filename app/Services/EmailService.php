<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Send email with high priority (password reset, verification, etc.)
     *
     * @param string $recipient
     * @param Mailable $mailable
     * @param int $delaySeconds Optional delay before sending
     * @return void
     */
    public static function sendHighPriority(string $recipient, Mailable $mailable, int $delaySeconds = 0): void
    {
        Log::info('Queueing high priority email', [
            'recipient' => $recipient,
            'mailable' => get_class($mailable),
            'delay' => $delaySeconds,
        ]);

        if ($delaySeconds > 0) {
            SendEmailJob::dispatch($recipient, $mailable, 'high')
                ->delay(now()->addSeconds($delaySeconds));
        } else {
            SendEmailJob::dispatch($recipient, $mailable, 'high');
        }
    }

    /**
     * Send email with default priority (registration, notifications, etc.)
     *
     * @param string $recipient
     * @param Mailable $mailable
     * @param int $delaySeconds Optional delay before sending
     * @return void
     */
    public static function sendDefault(string $recipient, Mailable $mailable, int $delaySeconds = 0): void
    {
        Log::info('Queueing default priority email', [
            'recipient' => $recipient,
            'mailable' => get_class($mailable),
            'delay' => $delaySeconds,
        ]);

        if ($delaySeconds > 0) {
            SendEmailJob::dispatch($recipient, $mailable, 'default')
                ->delay(now()->addSeconds($delaySeconds));
        } else {
            SendEmailJob::dispatch($recipient, $mailable, 'default');
        }
    }

    /**
     * Send email with low priority (newsletters, marketing, etc.)
     *
     * @param string $recipient
     * @param Mailable $mailable
     * @param int $delaySeconds Optional delay before sending
     * @return void
     */
    public static function sendLowPriority(string $recipient, Mailable $mailable, int $delaySeconds = 0): void
    {
        Log::info('Queueing low priority email', [
            'recipient' => $recipient,
            'mailable' => get_class($mailable),
            'delay' => $delaySeconds,
        ]);

        if ($delaySeconds > 0) {
            SendEmailJob::dispatch($recipient, $mailable, 'low')
                ->delay(now()->addSeconds($delaySeconds));
        } else {
            SendEmailJob::dispatch($recipient, $mailable, 'low');
        }
    }

    /**
     * Send multiple emails with staggered delays to prevent simultaneous sending
     *
     * @param array $emails Array of ['recipient' => string, 'mailable' => Mailable, 'priority' => string]
     * @param int $delayBetween Seconds between each email
     * @return void
     */
    public static function sendBatch(array $emails, int $delayBetween = 2): void
    {
        $delay = 0;

        foreach ($emails as $email) {
            $recipient = $email['recipient'];
            $mailable = $email['mailable'];
            $priority = $email['priority'] ?? 'default';

            Log::info('Queueing batch email', [
                'recipient' => $recipient,
                'mailable' => get_class($mailable),
                'priority' => $priority,
                'delay' => $delay,
            ]);

            SendEmailJob::dispatch($recipient, $mailable, $priority)
                ->delay(now()->addSeconds($delay));

            $delay += $delayBetween;
        }
    }
}
