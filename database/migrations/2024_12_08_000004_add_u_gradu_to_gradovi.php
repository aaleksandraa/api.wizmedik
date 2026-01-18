<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gradovi', function (Blueprint $table) {
            $table->string('u_gradu')->nullable()->after('naziv');
        });
    }

    public function down(): void
    {
        Schema::table('gradovi', function (Blueprint $table) {
            $table->dropColumn('u_gradu');
        });
    }
};
