<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Mail\Mailable;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 90;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = [10, 30, 60]; // Exponential backoff: 10s, 30s, 60s

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * The email recipient
     *
     * @var string
     */
    protected $recipient;

    /**
     * The mailable instance
     *
     * @var \Illuminate\Mail\Mailable
     */
    protected $mailable;

    /**
     * The email priority
     *
     * @var string
     */
    protected $priority;

    /**
     * Create a new job instance.
     */
    public function __construct(string $recipient, Mailable $mailable, string $priority = 'default')
    {
        $this->recipient = $recipient;
        $this->mailable = $mailable;
        $this->priority = $priority;

        // Set queue based on priority
        $this->onQueue($priority);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Rate limiting: max 60 emails per minute
        $executed = RateLimiter::attempt(
            'send-email',
            60, // Max 60 attempts per minute
            function() {
                $startTime = microtime(true);

                try {
                    Mail::to($this->recipient)->send($this->mailable);

                    $duration = round((microtime(true) - $startTime) * 1000, 2);

                    Log::info('Email sent successfully', [
                        'recipient' => $this->recipient,
                        'mailable' => get_class($this->mailable),
                        'priority' => $this->priority,
                        'duration_ms' => $duration,
                        'attempt' => $this->attempts(),
                    ]);
                } catch (\Exception $e) {
                    $duration = round((microtime(true) - $startTime) * 1000, 2);

                    Log::error('Email sending failed', [
                        'recipient' => $this->recipient,
                        'mailable' => get_class($this->mailable),
                        'priority' => $this->priority,
                        'error' => $e->getMessage(),
                        'duration_ms' => $duration,
                        'attempt' => $this->attempts(),
                    ]);

                    throw $e;
                }
            },
            60 // Per 60 seconds (1 minute)
        );

        if (!$executed) {
            // Rate limit exceeded, release job back to queue with delay
            Log::warning('Email rate limit exceeded, releasing job', [
                'recipient' => $this->recipient,
                'mailable' => get_class($this->mailable),
            ]);

            $this->release(5); // Release back to queue after 5 seconds
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Email job permanently failed', [
            'recipient' => $this->recipient,
            'mailable' => get_class($this->mailable),
            'priority' => $this->priority,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
        ]);

        // TODO: Send notification to admin about failed email
        // You can implement admin notification here
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'email',
            'priority:' . $this->priority,
            'mailable:' . class_basename($this->mailable),
        ];
    }
}
