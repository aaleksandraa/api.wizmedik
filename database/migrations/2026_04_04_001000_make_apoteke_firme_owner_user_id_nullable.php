<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('apoteke_firme', function (Blueprint $table) {
            $table->dropForeign(['owner_user_id']);
        });

        Schema::table('apoteke_firme', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable()->change();
        });

        Schema::table('apoteke_firme', function (Blueprint $table) {
            $table->foreign('owner_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('apoteke_firme')->whereNull('owner_user_id')->exists()) {
            throw new RuntimeException('Cannot make owner_user_id required while apoteke_firme records without owners exist.');
        }

        Schema::table('apoteke_firme', function (Blueprint $table) {
            $table->dropForeign(['owner_user_id']);
        });

        Schema::table('apoteke_firme', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable(false)->change();
        });

        Schema::table('apoteke_firme', function (Blueprint $table) {
            $table->foreign('owner_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
