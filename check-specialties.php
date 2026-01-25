<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get all specialties with parent info
$specialties = DB::table('specijalnosti')
    ->select('id', 'naziv', 'parent_id')
    ->orderBy('parent_id')
    ->orderBy('naziv')
    ->get();

echo "=== SPECIJALNOSTI (Hijerarhija) ===\n\n";

// Group by parent
$parents = $specialties->where('parent_id', null);
$children = $specialties->where('parent_id', '!=', null)->groupBy('parent_id');

echo "GLAVNE KATEGORIJE ({$parents->count()}):\n";
foreach ($parents as $parent) {
    echo "📁 {$parent->naziv} (ID: {$parent->id})\n";

    if (isset($children[$parent->id])) {
        foreach ($children[$parent->id] as $child) {
            echo "   └─ {$child->naziv} (ID: {$child->id})\n";
        }
    }
}

echo "\n\nPODKATEGORIJE BEZ RODITELJA:\n";
$orphans = $specialties->filter(function($s) use ($specialties) {
    return $s->parent_id && !$specialties->contains('id', $s->parent_id);
});

if ($orphans->count() > 0) {
    foreach ($orphans as $orphan) {
        echo "⚠️  {$orphan->naziv} (parent_id: {$orphan->parent_id} - ne postoji)\n";
    }
} else {
    echo "✅ Nema podkategorija bez roditelja\n";
}

echo "\n\nSTATISTIKA:\n";
echo "Ukupno specijalnosti: " . $specialties->count() . "\n";
echo "Glavne kategorije: " . $parents->count() . "\n";
echo "Podkategorije: " . $specialties->where('parent_id', '!=', null)->count() . "\n";
