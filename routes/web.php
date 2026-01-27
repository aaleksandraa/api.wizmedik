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
Route::get('/sitemap-cities.xml', [SitemapController::class, 'cities']);
Route::get('/sitemap-laboratories.xml', [SitemapController::class, 'laboratories']);
Route::get('/sitemap-spas.xml', [SitemapController::class, 'spas']);
Route::get('/sitemap-care-homes.xml', [SitemapController::class, 'careHomes']);
Route::get('/sitemap-blog.xml', [SitemapController::class, 'blog']);

// Serve storage files
Route::get('/storage/{folder}/{filename}', function ($folder, $filename) {
    $path = "public/{$folder}/{$filename}";

    if (!Storage::exists($path)) {
        abort(404);
    }

    $file = Storage::get($path);
    $mimeType = Storage::mimeType($path);

    return response($file, 200)->header('Content-Type', $mimeType);
})->where('folder', 'doctors|clinics|cities')->where('filename', '.*');

// SEO-friendly catch-all route (must be last)
// Serves index.html with dynamic meta tags for all SPA routes
// IMPORTANT: Exclude sitemap.xml and other XML files
Route::get('/{any}', [SeoController::class, 'index'])
    ->where('any', '^(?!sitemap).*')  // Exclude sitemap routes
    ->middleware('web');
