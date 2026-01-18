<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class GdprController extends Controller
{
    /**
     * Export all user data (GDPR Right to Data Portability)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function exportData(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Netačan password.'
            ], 422);
        }

        try {
            // Collect all user data
            $data = [
                'user' => [
                    'id' => $user->id,
                    'ime' => $user->ime,
                    'prezime' => $user->prezime,
                    'email' => $user->email,
                    'uloga' => $user->uloga,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'appointments' => [],
                'reviews' => [],
                'questions' => [],
                'notifications' => [],
            ];

            // Get appointments
            if ($user->uloga === 'pacijent') {
                $data['appointments'] = DB::table('termini')
                    ->where('user_id', $user->id)
                    ->select('id', 'doktor_id', 'datum_vrijeme', 'razlog', 'status', 'created_at')
                    ->get()
                    ->toArray();
            }

            // Get reviews
            $data['reviews'] = DB::table('recenzije')
                ->where('user_id', $user->id)
                ->select('id', 'recenziran_type', 'recenziran_id', 'ocjena', 'komentar', 'created_at')
                ->get()
                ->toArray();

            // Get questions
            $data['questions'] = DB::table('pitanja')
                ->where('email_korisnika', $user->email)
                ->select('id', 'naslov', 'sadrzaj', 'specijalnost_id', 'created_at')
                ->get()
                ->toArray();

            // Get notifications
            $data['notifications'] = DB::table('notifikacije')
                ->where('user_id', $user->id)
                ->select('id', 'tip', 'naslov', 'poruka', 'procitano', 'created_at')
                ->get()
                ->toArray();

            // If doctor, get doctor-specific data
            if ($user->uloga === 'doktor') {
                $doktor = DB::table('doktori')->where('user_id', $user->id)->first();
                if ($doktor) {
                    $data['doctor_profile'] = [
                        'id' => $doktor->id,
                        'specijalnost_id' => $doktor->specijalnost_id,
                        'telefon' => $doktor->telefon,
                        'adresa' => $doktor->adresa,
                        'grad' => $doktor->grad,
                        'opis' => $doktor->opis,
                        'created_at' => $doktor->created_at,
                    ];

                    // Get doctor's appointments
                    $data['doctor_appointments'] = DB::table('termini')
                        ->where('doktor_id', $doktor->id)
                        ->select('id', 'user_id', 'datum_vrijeme', 'status', 'created_at')
                        ->get()
                        ->toArray();

                    // Get answers to questions
                    $data['answers'] = DB::table('odgovori_na_pitanja')
                        ->where('doktor_id', $doktor->id)
                        ->select('id', 'pitanje_id', 'sadrzaj', 'created_at')
                        ->get()
                        ->toArray();
                }
            }

            // Log the export
            Log::channel('audit')->info('GDPR data export', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Podaci uspješno eksportovani.',
                'data' => $data,
                'exported_at' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            Log::error('GDPR export failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Greška prilikom eksportovanja podataka.'
            ], 500);
        }
    }

    /**
     * Delete all user data (GDPR Right to be Forgotten)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteData(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|string|in:DELETE MY DATA',
        ]);

        $user = $request->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Netačan password.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Log before deletion
            Log::channel('security')->warning('GDPR data deletion requested', [
                'user_id' => $user->id,
                'email' => $user->email,
                'uloga' => $user->uloga,
                'ip' => $request->ip(),
            ]);

            // Delete based on user role
            if ($user->uloga === 'doktor') {
                $doktor = DB::table('doktori')->where('user_id', $user->id)->first();
                if ($doktor) {
                    // Anonymize appointments instead of deleting (for medical records)
                    DB::table('termini')
                        ->where('doktor_id', $doktor->id)
                        ->update([
                            'razlog' => '[DELETED]',
                            'napomene' => '[DELETED]',
                        ]);

                    // Delete answers
                    DB::table('odgovori_na_pitanja')->where('doktor_id', $doktor->id)->delete();

                    // Delete doctor profile
                    DB::table('doktori')->where('id', $doktor->id)->delete();
                }
            } elseif ($user->uloga === 'pacijent') {
                // Anonymize appointments instead of deleting (for medical records)
                DB::table('termini')
                    ->where('user_id', $user->id)
                    ->update([
                        'user_id' => null,
                        'razlog' => '[DELETED]',
                        'napomene' => '[DELETED]',
                        'guest_ime' => 'Deleted User',
                        'guest_email' => null,
                        'guest_telefon' => null,
                    ]);
            }

            // Delete reviews
            DB::table('recenzije')->where('user_id', $user->id)->delete();

            // Anonymize questions (keep for public knowledge base)
            DB::table('pitanja')
                ->where('email_korisnika', $user->email)
                ->update([
                    'ime_korisnika' => 'Anonymous',
                    'email_korisnika' => null,
                    'ip_adresa' => null,
                ]);

            // Delete notifications
            DB::table('notifikacije')->where('user_id', $user->id)->delete();

            // Delete tokens
            $user->tokens()->delete();

            // Delete user account
            $user->delete();

            DB::commit();

            Log::channel('security')->info('GDPR data deletion completed', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'message' => 'Svi vaši podaci su uspješno obrisani. Vaš nalog je zatvoren.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('GDPR deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Greška prilikom brisanja podataka.'
            ], 500);
        }
    }

    /**
     * Get data retention policy
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function retentionPolicy()
    {
        return response()->json([
            'policy' => [
                'user_data' => [
                    'retention_period' => 'Indefinite (until account deletion)',
                    'description' => 'Osnovni podaci o korisniku se čuvaju dok je nalog aktivan.',
                ],
                'appointments' => [
                    'retention_period' => '7 years',
                    'description' => 'Medicinski zapisi se čuvaju 7 godina prema zakonu.',
                ],
                'reviews' => [
                    'retention_period' => 'Indefinite',
                    'description' => 'Recenzije se čuvaju za javni pregled.',
                ],
                'questions' => [
                    'retention_period' => 'Indefinite (anonymized)',
                    'description' => 'Pitanja se čuvaju anonimno za javnu bazu znanja.',
                ],
                'logs' => [
                    'security_logs' => '90 days',
                    'audit_logs' => '365 days',
                    'description' => 'Logovi se čuvaju za sigurnost i compliance.',
                ],
            ],
            'rights' => [
                'right_to_access' => 'Možete zatražiti kopiju svih vaših podataka.',
                'right_to_rectification' => 'Možete ažurirati svoje podatke u postavkama.',
                'right_to_erasure' => 'Možete zatražiti brisanje svih vaših podataka.',
                'right_to_data_portability' => 'Možete eksportovati svoje podatke u JSON formatu.',
                'right_to_object' => 'Možete se protiviti obradi vaših podataka.',
            ],
        ]);
    }

    /**
     * Request data rectification
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestRectification(Request $request)
    {
        $request->validate([
            'field' => 'required|string',
            'current_value' => 'required|string',
            'requested_value' => 'required|string',
            'reason' => 'required|string|max:500',
        ]);

        $user = $request->user();

        // Log the request
        Log::channel('audit')->info('GDPR rectification request', [
            'user_id' => $user->id,
            'email' => $user->email,
            'field' => $request->field,
            'reason' => $request->reason,
            'ip' => $request->ip(),
        ]);

        // In a real app, this would create a ticket for admin review
        // For now, we'll just log it

        return response()->json([
            'message' => 'Vaš zahtjev za ispravku podataka je primljen. Kontaktiraćemo vas uskoro.',
            'request_id' => uniqid('RECT-'),
        ]);
    }
}
