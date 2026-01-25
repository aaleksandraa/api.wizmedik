<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$doctors = DB::table('doktori')
    ->select('id', 'ime', 'prezime', 'specijalnost', 'slug')
    ->limit(10)
    ->get();

foreach ($doctors as $doctor) {
    echo "{$doctor->id} - Dr. {$doctor->ime} {$doctor->prezime} ({$doctor->specijalnost}) - slug: {$doctor->slug}\n";
}
