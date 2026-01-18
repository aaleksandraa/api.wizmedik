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
        Schema::table('banje', function (Blueprint $table) {
            $table->text('medicinsko_osoblje')->nullable()->after('fizijatar_prisutan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banje', function (Blueprint $table) {
            $table->dropColumn('medicinsko_osoblje');
        });
    }
};
