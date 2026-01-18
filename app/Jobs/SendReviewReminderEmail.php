<?php

namespace App\Jobs;

use App\Models\Termin;
use App\Models\Recenzija;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendReviewReminderEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $termin;

    public function __construct(Termin $termin)
    {
        $this->termin = $termin;
    }

    public function handle(): void
    {
        if ($this->termin->user && $this->termin->user->tip === 'pacijent') {
            $existingRecenzija = Recenzija::where('user_id', $this->termin->user_id)
                ->where('termin_id', $this->termin->id)
                ->first();

            if (!$existingRecenzija) {
                Mail::send('emails.review-reminder', [
                    'ime' => $this->termin->user->name,
                    'doktor' => $this->termin->doktor ? $this->termin->doktor->ime . ' ' . $this->termin->doktor->prezime : 'doktor',
                    'datum' => $this->termin->datum_vrijeme->format('d.m.Y'),
                ], function ($message) {
                    $message->to($this->termin->user->email)
                        ->subject('Ocijenite Vaš termin - Zdravlje BiH');
                });

                Recenzija::where('termin_id', $this->termin->id)->update(['email_poslat' => true]);
            }
        }
    }
}
