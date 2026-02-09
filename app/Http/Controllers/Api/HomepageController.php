<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HomepageController extends Controller
{
    /**
     * Get homepage data - SIMPLIFIED VERSION
     * This version tests each component separately to identify issues
     */
    public function getData()
    {
        try {
            Log::info('Homepage API called - starting data fetch');

            // Start with basic response structure
            $response = [
                'status' => 'OK',
                'timestamp' => now(),
                'settings' => [
                    'homepage_template' => 'custom2cyan',
                    'doctor_profile_template' => 'classic',
                    'clinic_profile_template' => 'classic',
                    'modern_cover_type' => 'gradient',
                    'modern_cover_value' => 'from-primary via-primary/90 to-primary/80',
                ],
                'doctors' => [],
                'clinics' => [],
                'banje' => [],
                'domovi' => [],
                'specialties' => [],
                'cities' => [],
                'all_cities' => [],
                'pitanja' => [],
                'blog_posts' => [],
                'filters' => [
                    'specialties' => [],
                    'cities' => []
                ]
            ];

            Log::info('Basic response structure created');

            // Test database connection first
            try {
                $dbTest = DB::connection()->getPdo();
                Log::info('Database connection successful');
            } catch (\Exception $e) {
                Log::error('Database connection failed: ' . $e->getMessage());
                return response()->json(array_merge($response, [
                    'error' => 'Database connection failed',
                    'message' => $e->getMessage()
                ]), 500);
            }

            // Try to get doctors (safest table)
            try {
                $doctors = DB::table('doktori')
                    ->where('aktivan', true)
                    ->whereNull('deleted_at')
                    ->select('id', 'slug', 'ime', 'prezime', 'specijalnost', 'grad')
                    ->limit(6)
                    ->get();

                $response['doctors'] = $doctors->toArray();
                Log::info('Doctors loaded: ' . count($doctors));
            } catch (\Exception $e) {
                Log::error('Failed to load doctors: ' . $e->getMessage());
                $response['doctors'] = [];
            }

            // Try to get clinics
            try {
                $clinics = DB::table('klinike')
                    ->where('aktivan', true)
                    ->whereNull('deleted_at')
                    ->select('id', 'slug', 'naziv', 'grad', 'adresa')
                    ->limit(4)
                    ->get();

                $response['clinics'] = $clinics->toArray();
                Log::info('Clinics loaded: ' . count($clinics));
            } catch (\Exception $e) {
                Log::error('Failed to load clinics: ' . $e->getMessage());
                $response['clinics'] = [];
            }

            // Try to get specialties
            try {
                $specialties = DB::table('specijalnosti')
                    ->whereNull('parent_id')
                    ->select('id', 'naziv', 'slug')
                    ->limit(8)
                    ->get();

                $response['specialties'] = $specialties->toArray();
                Log::info('Specialties loaded: ' . count($specialties));
            } catch (\Exception $e) {
                Log::error('Failed to load specialties: ' . $e->getMessage());
                $response['specialties'] = [];
            }

            // Try to get cities
            try {
                $cities = DB::table('gradovi')
                    ->select('id', 'naziv', 'slug')
                    ->limit(20)
                    ->get();

                $response['cities'] = $cities->toArray();
                $response['all_cities'] = $cities->toArray();
                Log::info('Cities loaded: ' . count($cities));
            } catch (\Exception $e) {
                Log::error('Failed to load cities: ' . $e->getMessage());
                $response['cities'] = [];
                $response['all_cities'] = [];
            }

            // Try to get banje
            try {
                $banje = DB::table('banje')
                    ->where('aktivan', true)
                    ->whereNull('deleted_at')
                    ->select('id', 'slug', 'naziv', 'grad', 'adresa')
                    ->limit(4)
                    ->get();

                $response['banje'] = $banje->toArray();
                Log::info('Banje loaded: ' . count($banje));
            } catch (\Exception $e) {
                Log::error('Failed to load banje: ' . $e->getMessage());
                $response['banje'] = [];
            }

            // Try to get domovi - THIS SHOULD BE EMPTY
            try {
                $domovi = DB::table('domovi_njega')
                    ->where('aktivan', true)
                    ->whereNull('deleted_at')
                    ->select('id', 'slug', 'naziv', 'grad', 'adresa')
                    ->limit(4)
                    ->get();

                $response['domovi'] = $domovi->toArray();
                Log::info('Domovi loaded: ' . count($domovi) . ' (should be 0)');

                if (count($domovi) > 0) {
                    Log::warning('Domovi found in database - this might be the issue');
                    foreach ($domovi as $dom) {
                        Log::info("Dom found: ID={$dom->id}, Naziv={$dom->naziv}");
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to load domovi: ' . $e->getMessage());
                $response['domovi'] = [];
            }

            // Try to get questions
            try {
                $pitanja = DB::table('pitanja')
                    ->where('je_javno', true)
                    ->select('id', 'naslov', 'slug', 'created_at')
                    ->limit(4)
                    ->get();

                $response['pitanja'] = $pitanja->toArray();
                Log::info('Pitanja loaded: ' . count($pitanja));
            } catch (\Exception $e) {
                Log::error('Failed to load pitanja: ' . $e->getMessage());
                $response['pitanja'] = [];
            }

            // Blog posts - skip for now to avoid model issues
            $response['blog_posts'] = [];
            Log::info('Blog posts skipped for safety');

            Log::info('Homepage API completed successfully');

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Homepage API fatal error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Homepage API failed',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : 'Enable debug mode for trace'
            ], 500);
        }
    }

    /**
     * Clear homepage cache
     */
    public function clearCache()
    {
        try {
            // No cache in this simple version
            Log::info('Cache clear requested (no cache in simple version)');
            return response()->json(['message' => 'Cache cleared (simple version)']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
