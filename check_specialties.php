<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$total = DB::table('specijalnosti')->count();
$main = DB::table('specijalnosti')->whereNull('parent_id')->count();
$sub = DB::table('specijalnosti')->whereNotNull('parent_id')->count();

echo "=================================\n";
echo "TRENUTNO STANJE SPECIJALNOSTI\n";
echo "=================================\n\n";
echo "Ukupno zapisa: $total\n";
echo "Glavne kategorije: $main\n";
echo "Podkategorije: $sub\n\n";

echo "GLAVNE KATEGORIJE:\n";
echo "---------------------------------\n";
$categories = DB::table('specijalnosti')->whereNull('parent_id')->orderBy('id')->get(['id', 'naziv']);
foreach ($categories as $cat) {
    $subCount = DB::table('specijalnosti')->where('parent_id', $cat->id)->count();
    echo "$cat->id. $cat->naziv ($subCount podkategorija)\n";
}

echo "\n=================================\n";
