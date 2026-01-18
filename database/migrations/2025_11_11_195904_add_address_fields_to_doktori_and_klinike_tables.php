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
        Schema::table('doktori', function (Blueprint $table) {
            $table->string('postanski_broj')->nullable()->after('lokacija');
            $table->string('mjesto')->nullable()->after('postanski_broj');
            $table->string('opstina')->nullable()->after('mjesto');
        });

        Schema::table('klinike', function (Blueprint $table) {
            $table->string('postanski_broj')->nullable()->after('adresa');
            $table->string('mjesto')->nullable()->after('postanski_broj');
            $table->string('opstina')->nullable()->after('mjesto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doktori', function (Blueprint $table) {
            $table->dropColumn(['postanski_broj', 'mjesto', 'opstina']);
        });

        Schema::table('klinike', function (Blueprint $table) {
            $table->dropColumn(['postanski_broj', 'mjesto', 'opstina']);
        });
    }
};
