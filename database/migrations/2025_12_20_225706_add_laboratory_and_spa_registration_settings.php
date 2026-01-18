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
        // Add laboratory and spa registration settings
        $settings = [
            // Laboratory registration
            ['key' => 'laboratory_registration_enabled', 'value' => 'true'],
            ['key' => 'laboratory_registration_free', 'value' => 'true'],
            ['key' => 'laboratory_registration_price', 'value' => '0'],
            ['key' => 'laboratory_auto_approve', 'value' => 'false'],
            ['key' => 'laboratory_registration_message', 'value' => 'Hvala na registraciji! Vaš zahtjev će biti pregledan u najkraćem roku.'],

            // Spa/Banja registration
            ['key' => 'spa_registration_enabled', 'value' => 'true'],
            ['key' => 'spa_registration_free', 'value' => 'true'],
            ['key' => 'spa_registration_price', 'value' => '0'],
            ['key' => 'spa_auto_approve', 'value' => 'false'],
            ['key' => 'spa_registration_message', 'value' => 'Hvala na registraciji! Vaš zahtjev će biti pregledan u najkraćem roku.'],

            // Auto-approve settings for existing types
            ['key' => 'doctor_auto_approve', 'value' => 'false'],
            ['key' => 'clinic_auto_approve', 'value' => 'false'],
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
            'laboratory_registration_enabled',
            'laboratory_registration_free',
            'laboratory_registration_price',
            'laboratory_auto_approve',
            'laboratory_registration_message',
            'spa_registration_enabled',
            'spa_registration_free',
            'spa_registration_price',
            'spa_auto_approve',
            'spa_registration_message',
            'doctor_auto_approve',
            'clinic_auto_approve',
        ];

        DB::table('site_settings')->whereIn('key', $keys)->delete();
    }
};
