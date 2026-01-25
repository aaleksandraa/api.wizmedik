<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class UploadController extends Controller
{
    public function uploadImage(Request $request)
    {
        try {
            $request->validate([
                'image'  => 'required|image|mimes:jpeg,png,jpg,webp,svg|max:5120', // 5MB max
                'folder' => 'required|in:doctors,clinics,cities,covers,blog,laboratories,spas,logos',
            ]);

            $folder = $request->folder;
            $imageFile = $request->file('image');

            // Security: Verify it's actually an image
            $mimeType = $imageFile->getMimeType();
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/svg+xml'];

            if (!in_array($mimeType, $allowedMimes)) {
                return response()->json(['error' => 'Invalid file type'], 400);
            }

            // Security: Check file size again (double check)
            if ($imageFile->getSize() > 5242880) { // 5MB in bytes
                return response()->json(['error' => 'File too large'], 400);
            }

            // Security: Verify image dimensions (prevent decompression bombs)
            try {
                $imageInfo = getimagesize($imageFile->getRealPath());
                if ($imageInfo === false) {
                    return response()->json(['error' => 'Invalid image file'], 400);
                }

                // Prevent extremely large images (max 10000x10000)
                if ($imageInfo[0] > 10000 || $imageInfo[1] > 10000) {
                    return response()->json(['error' => 'Image dimensions too large'], 400);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid image file'], 400);
            }

            // Unique ime fajla
            $extension = $imageFile->getClientOriginalExtension();

            // For SVG files, keep original format
            if (strtolower($extension) === 'svg') {
                $filename = time() . '_' . uniqid() . '.svg';
                $publicPath = "{$folder}/{$filename}";

                // Save SVG directly without processing
                Storage::disk('public')->put($publicPath, file_get_contents($imageFile->getRealPath()));

                $url = asset(Storage::disk('public')->url($publicPath));

                \Log::info('SVG uploaded successfully', [
                    'path' => $publicPath,
                    'url'  => $url,
                    'folder' => $folder
                ]);

                return response()->json([
                    'message' => 'Image uploaded successfully',
                    'url'     => $url,
                    'path'    => $publicPath,
                ]);
            }

            $filename = time() . '_' . uniqid() . '.webp';
            $path = "public/{$folder}/{$filename}";

            /*
                -- Obrada slike:
                1) Učitaj
                2) Resize based on folder:
                   - logos: max width 220px (height proportional to maintain original aspect ratio)
                   - blog: keep original size
                   - others: max 800x800
                3) Convert to WebP (80% quality)
            */

            $img = Image::read($imageFile);

            // Resize based on folder type
            if ($folder === 'logos') {
                // Get original dimensions
                $originalWidth = $img->width();
                $originalHeight = $img->height();
                $originalRatio = $originalWidth / $originalHeight;

                \Log::info('Logo resize - BEFORE', [
                    'width' => $originalWidth,
                    'height' => $originalHeight,
                    'ratio' => $originalRatio
                ]);

                // Logo optimization: max width 220px, height proportional (maintains aspect ratio)
                // If logo is 16:9, result will be 220x123.75px
                // If logo is 4:1, result will be 220x55px
                // If logo is 1:1, result will be 220x220px
                $img->resize(220, null, function ($constraint) {
                    $constraint->aspectRatio();  // Height calculated proportionally from original ratio
                    $constraint->upsize();       // Don't upscale small images
                });

                // Get new dimensions
                $newWidth = $img->width();
                $newHeight = $img->height();
                $newRatio = $newWidth / $newHeight;

                \Log::info('Logo resize - AFTER', [
                    'width' => $newWidth,
                    'height' => $newHeight,
                    'ratio' => $newRatio,
                    'ratio_maintained' => abs($originalRatio - $newRatio) < 0.01
                ]);
            } elseif ($folder !== 'blog') {
                // Standard images: max 800x800
                $img->resize(800, 800, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            // Blog images keep original size

            // WebP encoding
            $encoded = $img->toWebp(quality: 80);

            // Log file size
            \Log::info('Image processed', [
                'original_size' => strlen($imageFile->get()),
                'encoded_size'  => strlen($encoded),
                'path'          => $path
            ]);

            // Snimanje fajla na public disk
            $publicPath = "{$folder}/{$filename}";
            $saved = Storage::disk('public')->put($publicPath, $encoded);

            \Log::info('Storage put result', [
                'saved'       => $saved,
                'path'        => $publicPath,
                'exists_after'=> Storage::disk('public')->exists($publicPath)
            ]);

            if (!Storage::disk('public')->exists($publicPath)) {
                return response()->json([
                    'message' => 'Failed to save image',
                    'error'   => 'File not found after upload'
                ], 500);
            }

            // Generiši puni URL za frontend
            $url = asset(Storage::disk('public')->url($publicPath));

            \Log::info('Image uploaded successfully', [
                'path' => $publicPath,
                'url'  => $url,
                'folder' => $folder
            ]);

            return response()->json([
                'message' => 'Image uploaded successfully',
                'url'     => $url,
                'path'    => $publicPath,
            ]);

        } catch (\Exception $e) {

            \Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Image upload failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function deleteImage(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        if (Storage::disk('public')->exists($request->path)) {
            Storage::disk('public')->delete($request->path);
            return response()->json(['message' => 'Image deleted']);
        }

        return response()->json(['message' => 'Image not found'], 404);
    }
}
