<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert default listing template settings (use insertOrIgnore to avoid duplicates)
        $settings = [
            ['key' => 'doctors_listing_template', 'value' => 'default'],
            ['key' => 'clinics_listing_template', 'value' => 'default'],
            ['key' => 'cities_listing_template', 'value' => 'default'],
            ['key' => 'laboratories_listing_template', 'value' => 'default'],
        ];

        foreach ($settings as $setting) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('site_settings')->whereIn('key', [
            'doctors_listing_template',
            'clinics_listing_template',
            'cities_listing_template',
            'laboratories_listing_template',
        ])->delete();
    }
};
