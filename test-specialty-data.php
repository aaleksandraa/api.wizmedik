<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$specialty = App\Models\Specijalnost::first();

if ($specialty) {
    echo "Specialty ID: {$specialty->id}\n";
    echo "Naziv: {$specialty->naziv}\n";
    echo "Kljucne rijeci: " . json_encode($specialty->kljucne_rijeci) . "\n";
    echo "OG Image: " . ($specialty->og_image ?? 'NULL') . "\n";
    echo "\nAll data:\n";
    print_r($specialty->toArray());
} else {
    echo "No specialties found\n";
}
