<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find main specialties and add subspecialties
$subspecialties = [
    'Stomatologija' => [
        'Dječija stomatologija',
        'Ortodoncija',
        'Oralna hirurgija',
        'Parodontologija',
        'Protetika',
        'Endodoncija'
    ],
    'Kardiologija' => [
        'Interventna kardiologija',
        'Pedijatrijska kardiologija',
        'Elektrofiziologija'
    ],
    'Hirurgija' => [
        'Vaskularna hirurgija',
        'Torakalna hirurgija',
        'Abdominalna hirurgija',
        'Transplantaciona hirurgija'
    ],
    'Pedijatrija' => [
        'Neonatologija',
        'Pedijatrijska kardiologija',
        'Pedijatrijska neurologija',
        'Pedijatrijska gastroenterologija'
    ],
    'Ginekologija i akušerstvo' => [
        'Reproduktivna medicina',
        'Ginekološka onkologija',
        'Perinatologija',
        'Uroginekologija'
    ],
    'Neurologija' => [
        'Dječija neurologija',
        'Epileptologija',
        'Cerebrovaskularne bolesti'
    ],
    'Ortopedija' => [
        'Sportska medicina',
        'Artroskopska hirurgija',
        'Hirurgija kičme',
        'Dječija ortopedija'
    ]
];

$added = 0;
$skipped = 0;

foreach ($subspecialties as $parentName => $children) {
    // Find parent specialty
    $parent = DB::table('specijalnosti')
        ->where('naziv', $parentName)
        ->first();

    if (!$parent) {
        echo "⚠️  Roditelj '{$parentName}' ne postoji, preskačem...\n";
        continue;
    }

    echo "\n📁 {$parentName} (ID: {$parent->id})\n";

    foreach ($children as $childName) {
        // Check if already exists
        $exists = DB::table('specijalnosti')
            ->where('naziv', $childName)
            ->exists();

        if ($exists) {
            echo "   ⏭️  {$childName} - već postoji\n";
            $skipped++;
            continue;
        }

        // Create slug
        $slug = \Illuminate\Support\Str::slug($childName);

        // Insert subspecialty
        DB::table('specijalnosti')->insert([
            'naziv' => $childName,
            'slug' => $slug,
            'parent_id' => $parent->id,
            'opis' => null,
            'aktivan' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        echo "   ✅ {$childName}\n";
        $added++;
    }
}

echo "\n\n=== REZIME ===\n";
echo "Dodano: {$added} podkategorija\n";
echo "Preskočeno: {$skipped} (već postoje)\n";
echo "\nUkupno specijalnosti sada: " . DB::table('specijalnosti')->count() . "\n";
echo "Podkategorije: " . DB::table('specijalnosti')->whereNotNull('parent_id')->count() . "\n";
