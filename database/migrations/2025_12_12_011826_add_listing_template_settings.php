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
        // Insert default listing template settings
        DB::table('site_settings')->insert([
            [
                'key' => 'doctors_listing_template',
                'value' => 'default',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'clinics_listing_template',
                'value' => 'default',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'cities_listing_template',
                'value' => 'default',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'laboratories_listing_template',
                'value' => 'default',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
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
