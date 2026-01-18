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
        Schema::table('users', function (Blueprint $table) {
            $table->string('ime')->nullable()->after('name');
            $table->string('prezime')->nullable()->after('ime');
            $table->string('telefon')->nullable()->after('email');
            $table->date('datum_rodjenja')->nullable()->after('telefon');
            $table->string('adresa')->nullable()->after('datum_rodjenja');
            $table->string('grad')->nullable()->after('adresa');
            $table->enum('uloga', ['admin', 'doktor', 'pacijent', 'klinika'])->default('pacijent')->after('grad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ime', 'prezime', 'telefon', 'datum_rodjenja', 'adresa', 'grad', 'uloga']);
        });
    }
};
