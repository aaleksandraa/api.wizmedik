<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check services for Dr. Adnan Muratović (ID: 7)
$categories = DB::table('doktor_kategorije_usluga')
    ->where('doktor_id', 7)
    ->orderBy('redoslijed')
    ->get();

echo "=== Dr. Adnan Muratović (ID: 7) - Kategorije i Usluge ===\n\n";
echo "Ukupno kategorija: " . $categories->count() . "\n\n";

foreach ($categories as $category) {
    echo "📁 KATEGORIJA: {$category->naziv}\n";
    echo "   Opis: " . ($category->opis ?: 'Nema opisa') . "\n";

    $services = DB::table('usluge')
        ->where('kategorija_id', $category->id)
        ->orderBy('redoslijed')
        ->get();

    echo "   Usluge ({$services->count()}):\n";
    foreach ($services as $service) {
        echo "   • {$service->naziv}\n";
        echo "     - Opis: " . ($service->opis ?: 'Nema') . "\n";
        echo "     - Cijena: " . ($service->cijena ? $service->cijena . ' KM' : 'Na upit');
        if ($service->cijena_popust) {
            echo " (Popust: {$service->cijena_popust} KM)";
        }
        echo "\n";
        echo "     - Trajanje: {$service->trajanje_minuti} min\n";
    }
    echo "\n";
}

// Check uncategorized services
$uncategorized = DB::table('usluge')
    ->where('doktor_id', 7)
    ->whereNull('kategorija_id')
    ->get();

if ($uncategorized->count() > 0) {
    echo "📋 NEKATEGORISANE USLUGE ({$uncategorized->count()}):\n";
    foreach ($uncategorized as $service) {
        echo "• {$service->naziv} - {$service->cijena} KM - {$service->trajanje_minuti} min\n";
    }
}
