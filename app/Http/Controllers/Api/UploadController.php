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
                'image'  => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
                'folder' => 'required|in:doctors,clinics,cities,covers,blog,laboratories,spas',
            ]);

            $folder = $request->folder;
            $imageFile = $request->file('image');

            // Security: Verify it's actually an image
            $mimeType = $imageFile->getMimeType();
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

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
            $filename = time() . '_' . uniqid() . '.webp';
            $path = "public/{$folder}/{$filename}";

            /*
                -- Obrada slike:
                1) Učitaj
                2) Resize max 800x800, zadrži proporciju, ne povećavaj male slike
                3) Convert to WebP (80% quality)
            */

            $img = Image::read($imageFile);

            // For blog images, keep original size but optimize
            // For other folders, resize to max 800x800
            if ($folder !== 'blog') {
                $img->resize(800, 800, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

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
