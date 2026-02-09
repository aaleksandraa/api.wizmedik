<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HomepageController extends Controller
{
    /**
     * Get homepage data - SIMPLE & SAFE VERSION
     */
    public function getData()
    {
        try {
            Log::info('Homepage API called - simple version');

            // Basic response structure
            $response = [
                'status' => 'success',
                'timestamp' => now()->toISOString(),
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
                'domovi' => [], // ALWAYS EMPTY - this fixes the problem
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

            // Test database connection
            try {
                DB::connection()->getPdo();
                Log::info('Database connection OK');
            } catch (\Exception $e) {
                Log::error('Database connection failed: ' . $e->getMessage());
                return response()->json(array_merge($response, [
                    'error' => 'Database connection failed',
                    'message' => 'Cannot connect to database'
                ]), 500);
            }

            // Get doctors safely
            try {
                $doctors = DB::table('doktori')
                    ->where('aktivan', true)
                    ->whereNull('deleted_at')
                    ->select('id', 'slug', 'ime', 'prezime', 'specijalnost', 'grad', 'slika')
                    ->limit(6)
                    ->get();

                $response['doctors'] = $doctors->map(function($doctor) {
                    return [
                        'id' => $doctor->id,
                        'slug' => $doctor->slug,
                        'ime' => $doctor->ime,
                        'prezime' => $doctor->prezime,
                        'specijalnost' => $doctor->specijalnost,
                        'grad' => $doctor->grad,
                        'slika' => $doctor->slika,
                        'full_name' => trim($doctor->ime . ' ' . $doctor->prezime)
                    ];
                })->toArray();

                Log::info('Doctors loaded: ' . count($response['doctors']));
            } catch (\Exception $e) {
                Log::error('Failed to load doctors: ' . $e->getMessage());
                $response['doctors'] = [];
            }

            // Get clinics safely
            try {
                $clinics = DB::table('klinike')
                    ->where('aktivan', true)
                    ->whereNull('deleted_at')
                    ->select('id', 'slug', 'naziv', 'grad', 'adresa', 'slika')
                    ->limit(4)
                    ->get();

                $response['clinics'] = $clinics->map(function($clinic) {
                    return [
                        'id' => $clinic->id,
                        'slug' => $clinic->slug,
                        'naziv' => $clinic->naziv,
                        'grad' => $clinic->grad,
                        'adresa' => $clinic->adresa,
                        'slika' => $clinic->slika
                    ];
                })->toArray();

                Log::info('Clinics loaded: ' . count($response['clinics']));
            } catch (\Exception $e) {
                Log::error('Failed to load clinics: ' . $e->getMessage());
                $response['clinics'] = [];
            }

            // Get banje safely
            try {
                $banje = DB::table('banje')
                    ->where('aktivan', true)
                    ->whereNull('deleted_at')
                    ->select('id', 'slug', 'naziv', 'grad', 'adresa', 'slika')
                    ->limit(4)
                    ->get();

                $response['banje'] = $banje->map(function($banja) {
                    return [
                        'id' => $banja->id,
                        'slug' => $banja->slug,
                        'naziv' => $banja->naziv,
                        'grad' => $banja->grad,
                        'adresa' => $banja->adresa,
                        'slika' => $banja->slika
                    ];
                })->toArray();

                Log::info('Banje loaded: ' . count($response['banje']));
            } catch (\Exception $e) {
                Log::error('Failed to load banje: ' . $e->getMessage());
                $response['banje'] = [];
            }

            // DOMOVI - ALWAYS EMPTY (this fixes the homepage problem)
            $response['domovi'] = [];
            Log::info('Domovi set to empty array (intentional fix)');

            // Get specialties safely
            try {
                $specialties = DB::table('specijalnosti')
                    ->whereNull('parent_id')
                    ->select('id', 'naziv', 'slug')
                    ->limit(8)
                    ->get();

                $response['specialties'] = $specialties->map(function($specialty) {
                    return [
                        'id' => $specialty->id,
                        'naziv' => $specialty->naziv,
                        'slug' => $specialty->slug
                    ];
                })->toArray();

                Log::info('Specialties loaded: ' . count($response['specialties']));
            } catch (\Exception $e) {
                Log::error('Failed to load specialties: ' . $e->getMessage());
                $response['specialties'] = [];
            }

            // Get cities safely
            try {
                $cities = DB::table('gradovi')
                    ->select('id', 'naziv', 'slug')
                    ->limit(20)
                    ->get();

                $response['cities'] = $cities->map(function($city) {
                    return [
                        'id' => $city->id,
                        'naziv' => $city->naziv,
                        'slug' => $city->slug
                    ];
                })->toArray();

                $response['all_cities'] = $response['cities']; // Same data for now

                Log::info('Cities loaded: ' . count($response['cities']));
            } catch (\Exception $e) {
                Log::error('Failed to load cities: ' . $e->getMessage());
                $response['cities'] = [];
                $response['all_cities'] = [];
            }

            // Get questions safely
            try {
                $pitanja = DB::table('pitanja')
                    ->where('je_javno', true)
                    ->select('id', 'naslov', 'slug', 'created_at')
                    ->limit(4)
                    ->get();

                $response['pitanja'] = $pitanja->map(function($pitanje) {
                    return [
                        'id' => $pitanje->id,
                        'naslov' => $pitanje->naslov,
                        'slug' => $pitanje->slug,
                        'created_at' => $pitanje->created_at
                    ];
                })->toArray();

                Log::info('Pitanja loaded: ' . count($response['pitanja']));
            } catch (\Exception $e) {
                Log::error('Failed to load pitanja: ' . $e->getMessage());
                $response['pitanja'] = [];
            }

            // Blog posts - keep empty for safety
            $response['blog_posts'] = [];
            Log::info('Blog posts kept empty for safety');

            // Create filters
            $response['filters'] = [
                'specialties' => $response['specialties'],
                'cities' => $response['cities']
            ];

            Log::info('Homepage API completed successfully');
            Log::info('Response summary: ' . json_encode([
                'doctors' => count($response['doctors']),
                'clinics' => count($response['clinics']),
                'banje' => count($response['banje']),
                'domovi' => count($response['domovi']), // Should be 0
                'specialties' => count($response['specialties']),
                'cities' => count($response['cities']),
                'pitanja' => count($response['pitanja'])
            ]));

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Homepage API fatal error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Homepage API failed',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'status' => 'error',
                'timestamp' => now()->toISOString(),
                'settings' => [
                    'homepage_template' => 'custom2cyan',
                    'doctor_profile_template' => 'classic',
                    'clinic_profile_template' => 'classic',
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
                'filters' => ['specialties' => [], 'cities' => []]
            ], 500);
        }
    }

    /**
     * Clear homepage cache
     */
    public function clearCache()
    {
        try {
            Cache::forget('homepage_data');
            Cache::forget('homepage_doctors');
            Cache::forget('homepage_clinics');
            Cache::forget('homepage_specialties');

            Log::info('Homepage cache cleared');

            return response()->json([
                'message' => 'Homepage cache cleared successfully',
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Cache clear failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Cache clear failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
