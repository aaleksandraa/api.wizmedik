<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usluge', function (Blueprint $table) {
            $table->decimal('cijena_popust', 10, 2)->nullable()->after('cijena');
        });
    }

    public function down(): void
    {
        Schema::table('usluge', function (Blueprint $table) {
            $table->dropColumn('cijena_popust');
        });
    }
};
