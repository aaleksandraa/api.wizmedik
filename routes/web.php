<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SitemapController;

// Sitemap routes (must be before catch-all)
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages']);
Route::get('/sitemap-doctors.xml', [SitemapController::class, 'doctors']);
Route::get('/sitemap-clinics.xml', [SitemapController::class, 'clinics']);
Route::get('/sitemap-specialties.xml', [SitemapController::class, 'specialties']);
Route::get('/sitemap-service-pages.xml', [SitemapController::class, 'servicePages']);
Route::get('/sitemap-cities.xml', [SitemapController::class, 'cities']);
Route::get('/sitemap-laboratories.xml', [SitemapController::class, 'laboratories']);
Route::get('/sitemap-pharmacies.xml', [SitemapController::class, 'pharmacies']);
Route::get('/sitemap-spas.xml', [SitemapController::class, 'spas']);
Route::get('/sitemap-care-homes.xml', [SitemapController::class, 'careHomes']);
Route::get('/sitemap-doctor-city-specialties.xml', [SitemapController::class, 'doctorCitySpecialties']);
Route::get('/sitemap-blog.xml', [SitemapController::class, 'blog']);
Route::get('/sitemap-pitanja.xml', [SitemapController::class, 'questions']);
Route::get('/sitemap-lijekovi.xml', [SitemapController::class, 'medicines']);

// Serve storage files
Route::get('/storage/{folder}/{filename}', function ($folder, $filename) {
    $folder = trim((string) $folder);
    $filename = ltrim((string) $filename, '/');

    // Prevent path traversal attempts.
    if ($filename === '' || str_contains($filename, '..')) {
        abort(404);
    }

    $path = "{$folder}/{$filename}";
    $disk = Storage::disk('public');

    if (!$disk->exists($path)) {
        abort(404);
    }

    $file = $disk->get($path);
    $mimeType = $disk->mimeType($path);

    return response($file, 200)->header('Content-Type', $mimeType);
})->where('folder', 'doctors|clinics|cities|covers|blog|laboratories|spas|logos|backgrounds')
    ->where('filename', '.*');

// SEO-friendly catch-all route (must be last)
// Serves index.html with dynamic meta tags for all SPA routes
// IMPORTANT: Exclude API and sitemap routes so backend APIs never hit SPA fallback
Route::get('/{any}', [SeoController::class, 'index'])
    ->where('any', '^(?!(api|sitemap|storage)(/|$)).*')
    ->middleware('web');
