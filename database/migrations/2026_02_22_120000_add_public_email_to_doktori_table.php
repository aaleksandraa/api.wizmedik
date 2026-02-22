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
        if (!Schema::hasColumn('doktori', 'public_email')) {
            Schema::table('doktori', function (Blueprint $table) {
                $table->string('public_email')->nullable()->after('email');
                $table->index('public_email');
            });
        }

        // Preserve existing public visibility by copying legacy doctor email.
        DB::table('doktori')
            ->whereNull('public_email')
            ->update(['public_email' => DB::raw('email')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('doktori', 'public_email')) {
            Schema::table('doktori', function (Blueprint $table) {
                $table->dropIndex(['public_email']);
                $table->dropColumn('public_email');
            });
        }
    }
};
