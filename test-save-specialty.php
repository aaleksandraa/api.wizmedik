<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$specialty = App\Models\Specijalnost::first();

if ($specialty) {
    echo "Before update:\n";
    echo "Kljucne rijeci: " . json_encode($specialty->kljucne_rijeci) . "\n";
    echo "OG Image: " . ($specialty->og_image ?? 'NULL') . "\n\n";

    $specialty->update([
        'kljucne_rijeci' => ['test', 'keyword', 'proba'],
        'og_image' => 'https://example.com/test-image.jpg'
    ]);

    $specialty->refresh();

    echo "After update:\n";
    echo "Kljucne rijeci: " . json_encode($specialty->kljucne_rijeci) . "\n";
    echo "OG Image: " . ($specialty->og_image ?? 'NULL') . "\n";
} else {
    echo "No specialties found\n";
}
