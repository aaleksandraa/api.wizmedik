<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            ['key' => 'pharmacy_registration_enabled', 'value' => 'true'],
            ['key' => 'pharmacy_registration_free', 'value' => 'true'],
            ['key' => 'pharmacy_registration_price', 'value' => '0'],
            ['key' => 'pharmacy_auto_approve', 'value' => 'false'],
            ['key' => 'pharmacy_registration_message', 'value' => 'Hvala na registraciji! Vas zahtjev ce biti pregledan u najkracem roku.'],
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
        DB::table('site_settings')->whereIn('key', [
            'pharmacy_registration_enabled',
            'pharmacy_registration_free',
            'pharmacy_registration_price',
            'pharmacy_auto_approve',
            'pharmacy_registration_message',
        ])->delete();
    }
};

