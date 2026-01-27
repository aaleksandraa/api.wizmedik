<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Insert default template settings (use insertOrIgnore to avoid duplicates)
        $settings = [
            ['key' => 'doctor_profile_template', 'value' => 'classic'],
            ['key' => 'clinic_profile_template', 'value' => 'classic'],
            ['key' => 'homepage_template', 'value' => 'classic'],
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

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
