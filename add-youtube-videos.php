<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$youtubeVideos = [
    [
        'url' => 'https://www.youtube.com/watch?v=U-WV0g0rqNI',
        'naslov' => 'Savjeti za zdravlje - Dio 1'
    ],
    [
        'url' => 'https://www.youtube.com/watch?v=U-WV0g0rqNI',
        'naslov' => 'Prevencija bolesti - Edukativni video'
    ],
    [
        'url' => 'https://www.youtube.com/watch?v=U-WV0g0rqNI',
        'naslov' => 'Kako održati zdravlje - Praktični savjeti'
    ]
];

DB::table('doktori')
    ->where('id', 7)
    ->update([
        'youtube_linkovi' => json_encode($youtubeVideos),
        'updated_at' => now()
    ]);

echo "✅ YouTube video linkovi dodani za Dr. Adnan Muratović (ID: 7)\n";
echo "Profil: http://localhost:5173/doktor/adnan-muratovic\n";
echo "\nDodati video snimci:\n";
foreach ($youtubeVideos as $index => $video) {
    echo ($index + 1) . ". {$video['naslov']}\n";
    echo "   URL: {$video['url']}\n";
}
