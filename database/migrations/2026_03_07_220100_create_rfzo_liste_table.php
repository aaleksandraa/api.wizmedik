<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfzo_liste', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('naziv', 128)->nullable();
            $table->text('pojasnjenje')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        DB::table('rfzo_liste')->insert([
            [
                'code' => 'A',
                'naziv' => 'Lista A',
                'pojasnjenje' => 'Lijekovi koji se propisuju i izdaju na obrascu ljekarskog recepta.',
                'sort_order' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'A1',
                'naziv' => 'Lista A1',
                'pojasnjenje' => 'Lekovi koji se propisuju i izdaju na obrascu lekarskog recepta, a koji imaju terapijsku paralelu (terapijsku alternativu) lekovima u Listi A.',
                'sort_order' => 20,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'B',
                'naziv' => 'Lista B',
                'pojasnjenje' => 'Lijekovi koji se propisuju i izdaju na obrascu ljekarskog recepta uz visu participaciju osiguranika.',
                'sort_order' => 30,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('rfzo_liste');
    }
};
