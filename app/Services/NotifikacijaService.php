<?php

namespace App\Services;

use App\Models\Notifikacija;
use App\Mail\TerminZakazan;
use App\Mail\TerminOtkazan;
use App\Mail\GostovanjePoziv;
use App\Mail\KlinikaZahtjev;
use App\Mail\OdgovorNaPitanjeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotifikacijaService
{
    /**
     * Send notification for new appointment
     */
    public static function terminZakazan($termin)
    {
        $doktor = $termin->doktor;
        $terminData = [
            'termin_id' => $termin->id,
            'datum' => \Carbon\Carbon::parse($termin->datum_vrijeme)->format('Y-m-d'),
        ];

        // Notify doctor
        if ($doktor && $doktor->user_id) {
            self::createNotifikacija(
                $doktor->user_id,
                'termin_zakazan',
                'Novi termin zakazan',
                self::getTerminPoruka($termin, 'doctor'),
                $terminData
            );

            // Send email to doctor's account email (users.email), fallback legacy doctor email.
            $doctorEmail = self::resolveDoctorEmail($doktor);
            if (!empty($doctorEmail)) {
                try {
                    Mail::to($doctorEmail)->send(new TerminZakazan($termin, 'doctor'));
                } catch (\Throwable $e) {
                    Log::error('Failed to send email to doctor: ' . $e->getMessage());
                }
            }
        }

        // Notify clinic if doctor belongs to one
        if ($doktor && $doktor->klinika_id && $doktor->klinika) {
            $klinika = $doktor->klinika;
            if ($klinika->user_id) {
                self::createNotifikacija(
                    $klinika->user_id,
                    'termin_zakazan',
                    'Novi termin u klinici',
                    self::getTerminPoruka($termin, 'clinic'),
                    array_merge($terminData, ['doktor_id' => $doktor->id])
                );

                // Send email to clinic
                if ($klinika->contact_email) {
                    try {
                        Mail::to($klinika->contact_email)->send(new TerminZakazan($termin, 'clinic'));
                    } catch (\Throwable $e) {
                        Log::error('Failed to send email to clinic: ' . $e->getMessage());
                    }
                }
            }
        }

        // Notify patient (if registered user)
        if ($termin->user_id) {
            self::createNotifikacija(
                $termin->user_id,
                'termin_zakazan',
                'Termin uspjeÅ¡no zakazan',
                self::getTerminPoruka($termin, 'patient'),
                $terminData
            );

            // Send email to patient
            if ($termin->user && $termin->user->email) {
                try {
                    Mail::to($termin->user->email)->send(new TerminZakazan($termin, 'patient'));
                } catch (\Throwable $e) {
                    Log::error('Failed to send email to patient: ' . $e->getMessage());
                }
            }
        } elseif ($termin->guest_email) {
            // Send email to guest
            try {
                Mail::to($termin->guest_email)->send(new TerminZakazan($termin, 'patient'));
            } catch (\Throwable $e) {
                Log::error('Failed to send email to guest: ' . $e->getMessage());
            }
        }
    }

    /**
     * Send notification for guest doctor invitation
     */
    public static function gostovanjePoziv($gostovanje, $klinika, $doktor)
    {
        if ($doktor->user_id) {
            self::createNotifikacija(
                $doktor->user_id,
                'gostovanje_poziv',
                'Poziv za gostovanje',
                "Klinika {$klinika->naziv} vas poziva na gostovanje dana " .
                    \Carbon\Carbon::parse($gostovanje->datum)->format('d.m.Y.'),
                [
                    'gostovanje_id' => $gostovanje->id,
                    'klinika_id' => $klinika->id,
                    'datum' => $gostovanje->datum
                ]
            );
        }

        // Send email
        $doctorEmail = self::resolveDoctorEmail($doktor);
        if ($doctorEmail) {
            try {
                Mail::to($doctorEmail)->send(new GostovanjePoziv($gostovanje, $klinika, $doktor));
            } catch (\Throwable $e) {
                Log::error('Failed to send gostovanje email: ' . $e->getMessage());
            }
        }
    }

    /**
     * Send notification when doctor requests to join clinic
     */
    public static function doktorZahtjevKlinici($zahtjev, $klinika, $doktor)
    {
        if ($klinika->user_id) {
            self::createNotifikacija(
                $klinika->user_id,
                'doktor_zahtjev',
                'Novi zahtjev za pridruÅ¾ivanje',
                "Dr. {$doktor->ime} {$doktor->prezime} ({$doktor->specijalnost}) Å¾eli se pridruÅ¾iti vaÅ¡oj klinici.",
                ['zahtjev_id' => $zahtjev->id, 'doktor_id' => $doktor->id]
            );
        }

        // Send email to clinic
        if ($klinika->contact_email) {
            try {
                Mail::to($klinika->contact_email)->send(new KlinikaZahtjev($zahtjev, $klinika, $doktor, 'doctor_request'));
            } catch (\Throwable $e) {
                Log::error('Failed to send clinic request email: ' . $e->getMessage());
            }
        }
    }

    /**
     * Send notification when clinic invites doctor
     */
    public static function klinikaPozivDoktoru($zahtjev, $klinika, $doktor)
    {
        if ($doktor->user_id) {
            self::createNotifikacija(
                $doktor->user_id,
                'klinika_poziv',
                'Poziv za pridruÅ¾ivanje klinici',
                "Klinika {$klinika->naziv} vas poziva da se pridruÅ¾ite njihovom timu.",
                ['zahtjev_id' => $zahtjev->id, 'klinika_id' => $klinika->id]
            );
        }

        // Send email to doctor
        $doctorEmail = self::resolveDoctorEmail($doktor);
        if ($doctorEmail) {
            try {
                Mail::to($doctorEmail)->send(new KlinikaZahtjev($zahtjev, $klinika, $doktor, 'clinic_invitation'));
            } catch (\Throwable $e) {
                Log::error('Failed to send doctor invitation email: ' . $e->getMessage());
            }
        }
    }

    /**
     * Create notification record
     */
    private static function createNotifikacija($userId, $tip, $naslov, $poruka, $data = [])
    {
        return Notifikacija::create([
            'user_id' => $userId,
            'tip' => $tip,
            'naslov' => $naslov,
            'poruka' => $poruka,
            'data' => $data,
        ]);
    }

    /**
     * Resolve doctor notification email from linked user account.
     */
    private static function resolveDoctorEmail($doktor): ?string
    {
        if (!$doktor) {
            return null;
        }

        if ($doktor->relationLoaded('user') && $doktor->user?->email) {
            return $doktor->user->email;
        }

        if ($doktor->user_id) {
            $doktor->loadMissing('user:id,email');
            if ($doktor->user?->email) {
                return $doktor->user->email;
            }
        }

        // Legacy fallback (pre user_id linkage)
        return $doktor->email ?: null;
    }

    /**
     * Send notification for new public question to doctors with matching specialty
     */
    public static function novoPitanje($pitanje, $doktori)
    {
        foreach ($doktori as $doktor) {
            if ($doktor->user_id) {
                self::createNotifikacija(
                    $doktor->user_id,
                    'novo_pitanje',
                    'Novo pitanje iz vaše specijalnosti',
                    "Korisnik {$pitanje->ime_korisnika} je postavio pitanje: \"{$pitanje->naslov}\"",
                    [
                        'pitanje_id' => $pitanje->id,
                        'pitanje_slug' => $pitanje->slug,
                        'specijalnost_id' => $pitanje->specijalnost_id,
                    ]
                );
            }
        }
    }

    /**
     * Send notification when doctor answers a question
     */
    public static function odgovorNaPitanje($pitanje, $odgovor)
    {
        $doktorIme = "Dr. {$odgovor->doktor->ime} {$odgovor->doktor->prezime}";

        // If question was asked by logged-in user, send notification
        if ($pitanje->user_id) {
            self::createNotifikacija(
                $pitanje->user_id,
                'odgovor_na_pitanje',
                'Dobili ste odgovor na vaše pitanje',
                "{$doktorIme} je odgovorio na vaše pitanje: \"{$pitanje->naslov}\"",
                [
                    'pitanje_id' => $pitanje->id,
                    'pitanje_slug' => $pitanje->slug,
                    'odgovor_id' => $odgovor->id,
                    'doktor_id' => $odgovor->doktor_id,
                ]
            );

            // Also send email to logged-in user
            if ($pitanje->user && $pitanje->user->email) {
                try {
                    Mail::to($pitanje->user->email)->send(new OdgovorNaPitanjeMail($pitanje, $odgovor));
                } catch (\Throwable $e) {
                    Log::error('Failed to send answer notification email to user: ' . $e->getMessage());
                }
            }
        }
        // If guest left email, send email notification
        elseif ($pitanje->email_korisnika) {
            try {
                Mail::to($pitanje->email_korisnika)->send(new OdgovorNaPitanjeMail($pitanje, $odgovor));
            } catch (\Throwable $e) {
                Log::error('Failed to send answer notification email to guest: ' . $e->getMessage());
            }
        }

        // Mark question as answered if this is the first answer
        if (!$pitanje->je_odgovoreno) {
            $pitanje->oznacKaoOdgovoreno();
        }
    }

    /**
     * Get appointment message based on recipient type
     */
    private static function getTerminPoruka($termin, $recipientType)
    {
        $datum = \Carbon\Carbon::parse($termin->datum_vrijeme)->format('d.m.Y. H:i');

        if ($recipientType === 'patient') {
            return "VaÅ¡ termin kod Dr. {$termin->doktor->ime} {$termin->doktor->prezime} je zakazan za {$datum}.";
        } elseif ($recipientType === 'doctor') {
            $pacijent = $termin->user
                ? "{$termin->user->ime} {$termin->user->prezime}"
                : "{$termin->guest_ime} {$termin->guest_prezime}";
            return "Novi termin zakazan: {$pacijent} - {$datum}";
        } else {
            $pacijent = $termin->user
                ? "{$termin->user->ime} {$termin->user->prezime}"
                : "{$termin->guest_ime} {$termin->guest_prezime}";
            return "Novi termin kod Dr. {$termin->doktor->ime} {$termin->doktor->prezime}: {$pacijent} - {$datum}";
        }
    }

    /**
     * Send notification for cancelled appointment
     * @param $termin - The appointment being cancelled
     * @param string $cancelledBy - Who cancelled: 'patient', 'doctor', or 'clinic'
     */
    public static function terminOtkazan($termin, $cancelledBy = 'patient')
    {
        $doktor = $termin->doktor;
        $terminData = [
            'termin_id' => $termin->id,
            'datum' => \Carbon\Carbon::parse($termin->datum_vrijeme)->format('Y-m-d'),
            'cancelled_by' => $cancelledBy,
        ];

        // Notify doctor (if not cancelled by doctor)
        if ($cancelledBy !== 'doctor' && $doktor && $doktor->user_id) {
            self::createNotifikacija(
                $doktor->user_id,
                'termin_otkazan',
                'Termin otkazan',
                self::getTerminOtkazanPoruka($termin, 'doctor', $cancelledBy),
                $terminData
            );

            // Send email to doctor's account email (users.email), fallback legacy doctor email.
            $doctorEmail = self::resolveDoctorEmail($doktor);
            if ($doctorEmail) {
                try {
                    Mail::to($doctorEmail)->send(new TerminOtkazan($termin, 'doctor', $cancelledBy));
                } catch (\Throwable $e) {
                    Log::error('Failed to send cancellation email to doctor: ' . $e->getMessage());
                }
            }
        }

        // Notify clinic if doctor belongs to one (if not cancelled by clinic)
        if ($cancelledBy !== 'clinic' && $doktor && $doktor->klinika_id && $doktor->klinika) {
            $klinika = $doktor->klinika;
            if ($klinika->user_id) {
                self::createNotifikacija(
                    $klinika->user_id,
                    'termin_otkazan',
                    'Termin otkazan u klinici',
                    self::getTerminOtkazanPoruka($termin, 'clinic', $cancelledBy),
                    array_merge($terminData, ['doktor_id' => $doktor->id])
                );

                // Send email to clinic
                if ($klinika->contact_email) {
                    try {
                        Mail::to($klinika->contact_email)->send(new TerminOtkazan($termin, 'clinic', $cancelledBy));
                    } catch (\Throwable $e) {
                        Log::error('Failed to send cancellation email to clinic: ' . $e->getMessage());
                    }
                }
            }
        }

        // Notify patient (if not cancelled by patient)
        if ($cancelledBy !== 'patient') {
            if ($termin->user_id) {
                self::createNotifikacija(
                    $termin->user_id,
                    'termin_otkazan',
                    'VaÅ¡ termin je otkazan',
                    self::getTerminOtkazanPoruka($termin, 'patient', $cancelledBy),
                    $terminData
                );

                // Send email to patient
                if ($termin->user && $termin->user->email) {
                    try {
                        Mail::to($termin->user->email)->send(new TerminOtkazan($termin, 'patient', $cancelledBy));
                    } catch (\Throwable $e) {
                        Log::error('Failed to send cancellation email to patient: ' . $e->getMessage());
                    }
                }
            } elseif ($termin->guest_email) {
                // Send email to guest
                try {
                    Mail::to($termin->guest_email)->send(new TerminOtkazan($termin, 'patient', $cancelledBy));
                } catch (\Throwable $e) {
                    Log::error('Failed to send cancellation email to guest: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Get cancellation message based on recipient type and who cancelled
     */
    private static function getTerminOtkazanPoruka($termin, $recipientType, $cancelledBy)
    {
        $datum = \Carbon\Carbon::parse($termin->datum_vrijeme)->format('d.m.Y. H:i');
        $pacijent = $termin->user
            ? "{$termin->user->ime} {$termin->user->prezime}"
            : "{$termin->guest_ime} {$termin->guest_prezime}";

        $cancelledByText = match($cancelledBy) {
            'patient' => 'pacijenta',
            'doctor' => 'doktora',
            'clinic' => 'klinike',
            default => 'korisnika',
        };

        if ($recipientType === 'patient') {
            return "VaÅ¡ termin kod Dr. {$termin->doktor->ime} {$termin->doktor->prezime} za {$datum} je otkazan od strane {$cancelledByText}.";
        } elseif ($recipientType === 'doctor') {
            return "Termin sa pacijentom {$pacijent} za {$datum} je otkazan od strane {$cancelledByText}.";
        } else {
            return "Termin kod Dr. {$termin->doktor->ime} {$termin->doktor->prezime} sa pacijentom {$pacijent} za {$datum} je otkazan od strane {$cancelledByText}.";
        }
    }
}

