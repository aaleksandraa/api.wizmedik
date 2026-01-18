<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doktori', function (Blueprint $table) {
            $table->boolean('auto_potvrda')->default(false)->after('prihvata_online');
        });
    }

    public function down(): void
    {
        Schema::table('doktori', function (Blueprint $table) {
            $table->dropColumn('auto_potvrda');
        });
    }
};
