<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check services for Dr. Adnan Muratović
$services = DB::table('usluge')
    ->where('doktor_id', 7)
    ->select('id', 'naziv', 'opis', 'cijena', 'cijena_popust', 'trajanje_minuti', 'kategorija_id')
    ->get();

echo "Usluge za Dr. Adnan Muratović (ID: 7):\n";
echo "Ukupno usluga: " . $services->count() . "\n\n";

if ($services->count() > 0) {
    foreach ($services as $service) {
        echo "ID: {$service->id}\n";
        echo "Naziv: {$service->naziv}\n";
        echo "Opis: " . ($service->opis ?: 'Nema opisa') . "\n";
        echo "Cijena: " . ($service->cijena ? $service->cijena . ' KM' : 'Na upit') . "\n";
        echo "Popust: " . ($service->cijena_popust ? $service->cijena_popust . ' KM' : 'Nema') . "\n";
        echo "Trajanje: {$service->trajanje_minuti} min\n";
        echo "Kategorija ID: " . ($service->kategorija_id ?: 'Nekategorisano') . "\n";
        echo "---\n";
    }
} else {
    echo "Nema usluga za ovog doktora.\n";
}

// Check categories
$categories = DB::table('doktor_kategorije_usluga')
    ->where('doktor_id', 7)
    ->select('id', 'naziv', 'opis', 'redoslijed')
    ->orderBy('redoslijed')
    ->get();

echo "\nKategorije usluga:\n";
echo "Ukupno kategorija: " . $categories->count() . "\n\n";

if ($categories->count() > 0) {
    foreach ($categories as $cat) {
        echo "ID: {$cat->id}\n";
        echo "Naziv: {$cat->naziv}\n";
        echo "Opis: " . ($cat->opis ?: 'Nema opisa') . "\n";
        echo "Redoslijed: {$cat->redoslijed}\n";
        echo "---\n";
    }
}
