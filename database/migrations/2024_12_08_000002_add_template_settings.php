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

        // Insert default template settings
        DB::table('site_settings')->insert([
            ['key' => 'doctor_profile_template', 'value' => 'classic', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'clinic_profile_template', 'value' => 'classic', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'homepage_template', 'value' => 'classic', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
