<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add registration settings to site_settings table
        $settings = [
            // Doctor registration
            ['key' => 'doctor_registration_enabled', 'value' => 'true'],
            ['key' => 'doctor_registration_free', 'value' => 'false'],
            ['key' => 'doctor_registration_price', 'value' => '0'],
            ['key' => 'doctor_registration_message', 'value' => 'Hvala na registraciji! Vaš zahtjev će biti pregledan u najkraćem roku.'],

            // Clinic registration
            ['key' => 'clinic_registration_enabled', 'value' => 'true'],
            ['key' => 'clinic_registration_free', 'value' => 'false'],
            ['key' => 'clinic_registration_price', 'value' => '0'],
            ['key' => 'clinic_registration_message', 'value' => 'Hvala na registraciji! Vaš zahtjev će biti pregledan u najkraćem roku.'],

            // Email settings
            ['key' => 'registration_admin_email', 'value' => 'admin@wizmedik.ba'],
            ['key' => 'registration_auto_approve', 'value' => 'false'],

            // Security settings
            ['key' => 'registration_require_documents', 'value' => 'true'],
            ['key' => 'registration_max_attempts', 'value' => '3'],
            ['key' => 'registration_expiry_days', 'value' => '7'],
        ];

        foreach ($settings as $setting) {
            DB::table('site_settings')->insertOrIgnore([
                'key' => $setting['key'],
                'value' => $setting['value'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $keys = [
            'doctor_registration_enabled',
            'doctor_registration_free',
            'doctor_registration_price',
            'doctor_registration_message',
            'clinic_registration_enabled',
            'clinic_registration_free',
            'clinic_registration_price',
            'clinic_registration_message',
            'registration_admin_email',
            'registration_auto_approve',
            'registration_require_documents',
            'registration_max_attempts',
            'registration_expiry_days',
        ];

        DB::table('site_settings')->whereIn('key', $keys)->delete();
    }
};
