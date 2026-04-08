<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use RuntimeException;

class UploadController extends Controller
{
    public function uploadImage(Request $request)
    {
        try {
            $request->validate([
                'image'  => 'required|image|mimes:jpeg,png,jpg,webp,svg|max:5120', // 5MB max
                'folder' => 'required|in:doctors,clinics,cities,covers,blog,laboratories,spas,logos,backgrounds,pharmacies,care-homes',
            ]);

            $folder = $request->folder;
            $imageFile = $request->file('image');

            // Security: Verify it's actually an image
            $mimeType = $imageFile->getMimeType();
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/pjpeg', 'image/x-png', 'image/webp', 'image/svg+xml'];

            if (!in_array($mimeType, $allowedMimes)) {
                return response()->json(['error' => 'Invalid file type'], 400);
            }

            // Security: Check file size again (double check)
            if ($imageFile->getSize() > 5242880) { // 5MB in bytes
                return response()->json(['error' => 'File too large'], 400);
            }

            $extension = strtolower((string) $imageFile->getClientOriginalExtension());
            $baseFilename = time() . '_' . uniqid();

            // SVG: save directly without raster processing
            if ($extension === 'svg') {
                $stored = $this->storeOriginalUpload($request, $imageFile, $folder, $baseFilename, 'svg');

                \Log::info('SVG uploaded successfully', [
                    'path' => $stored['path'],
                    'url'  => $stored['url'],
                    'folder' => $folder,
                    'base_url' => $request->getSchemeAndHttpHost(),
                ]);

                return response()->json([
                    'message' => 'Image uploaded successfully',
                    'url'     => $stored['url'],
                    'path'    => $stored['path'],
                ]);
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

            /*
                -- Obrada slike:
                1) Učitaj
                2) Resize based on folder:
                   - logos: max width 220px (height proportional to maintain original aspect ratio)
                   - blog: keep original size
                   - others: max 800x800
                3) Convert to WebP (80% quality)
            */

            try {
                $img = Image::read($imageFile);
            } catch (\Throwable $decodeException) {
                \Log::warning('Image decode failed', [
                    'error' => $decodeException->getMessage(),
                    'folder' => $folder,
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                ]);

                $stored = $this->storeOriginalUpload($request, $imageFile, $folder, $baseFilename, $extension);

                return response()->json([
                    'message' => 'Image uploaded successfully',
                    'url'     => $stored['url'],
                    'path'    => $stored['path'],
                    'fallback'=> 'original',
                ]);
            }

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

                // Fixed max height: 70px, proportional width (Intervention v3 compatible)
                $targetHeight = 70;
                $img->scaleDown(null, $targetHeight);

                // Get new dimensions for verification
                $newWidth = $img->width();
                $newHeight = $img->height();
                $newRatio = $newWidth / $newHeight;

                \Log::info('Logo resize - AFTER', [
                    'target_height' => $targetHeight,
                    'actual_width' => $newWidth,
                    'actual_height' => $newHeight,
                    'original_ratio' => $originalRatio,
                    'new_ratio' => $newRatio,
                    'ratio_maintained' => abs($originalRatio - $newRatio) < 0.01
                ]);
            } elseif ($folder === 'blog') {
                // Blog / service editor images: keep quality, but cap max size to prevent memory crashes
                $img->scaleDown(2400, 2400);
            } else {
                // Standard images: max 800x800
                $img->scaleDown(800, 800);
            }

            $publicPath = '';
            $encodedBinary = '';

            // Try WebP first, fallback to JPEG if WebP is unavailable on server
            try {
                $publicPath = "{$folder}/{$baseFilename}.webp";
                $encodedBinary = (string) $img->toWebp(quality: 80);
            } catch (\Throwable $webpException) {
                \Log::warning('WebP encoding failed, falling back to JPEG', [
                    'error' => $webpException->getMessage(),
                    'folder' => $folder,
                ]);

                try {
                    $publicPath = "{$folder}/{$baseFilename}.jpg";
                    $encodedBinary = (string) $img->toJpeg(quality: 85);
                } catch (\Throwable $jpegException) {
                    \Log::warning('JPEG encoding fallback failed, storing original bytes', [
                        'error' => $jpegException->getMessage(),
                        'folder' => $folder,
                    ]);

                    $safeExtension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)
                        ? $extension
                        : 'jpg';
                    $publicPath = "{$folder}/{$baseFilename}.{$safeExtension}";
                    $encodedBinary = (string) file_get_contents($imageFile->getRealPath());
                }
            }

            // Log file size
            \Log::info('Image processed', [
                'original_size' => $imageFile->getSize(),
                'encoded_size'  => strlen($encodedBinary),
                'path'          => $publicPath,
                'disk_root'     => config('filesystems.disks.public.root'),
            ]);

            try {
                $saved = Storage::disk('public')->put($publicPath, $encodedBinary);

                \Log::info('Storage put result', [
                    'saved'       => $saved,
                    'path'        => $publicPath,
                    'exists_after'=> Storage::disk('public')->exists($publicPath),
                ]);

                if (!$saved || !Storage::disk('public')->exists($publicPath)) {
                    throw new RuntimeException('Processed image write failed on public disk.');
                }
            } catch (\Throwable $storageException) {
                \Log::warning('Processed image write failed, falling back to original upload', [
                    'error' => $storageException->getMessage(),
                    'path' => $publicPath,
                    'folder' => $folder,
                ]);

                $stored = $this->storeOriginalUpload($request, $imageFile, $folder, $baseFilename, $extension);

                return response()->json([
                    'message' => 'Image uploaded successfully',
                    'url'     => $stored['url'],
                    'path'    => $stored['path'],
                    'fallback'=> 'original',
                ]);
            }

            $url = $this->buildPublicUrl($request, $publicPath);

            \Log::info('Image uploaded successfully', [
                'path' => $publicPath,
                'url'  => $url,
                'folder' => $folder,
                'base_url' => $request->getSchemeAndHttpHost(),
            ]);

            return response()->json([
                'message' => 'Image uploaded successfully',
                'url'     => $url,
                'path'    => $publicPath,
            ]);

        } catch (\Exception $e) {

            \Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'disk_root' => config('filesystems.disks.public.root'),
                'request_host' => $request->getSchemeAndHttpHost(),
            ]);

            return response()->json([
                'message' => 'Image upload failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @return array{path:string,url:string}
     */
    private function storeOriginalUpload(
        Request $request,
        UploadedFile $imageFile,
        string $folder,
        string $baseFilename,
        ?string $preferredExtension = null
    ): array {
        $extension = strtolower((string) ($preferredExtension ?: $imageFile->getClientOriginalExtension()));
        $safeExtension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'svg'], true)
            ? $extension
            : strtolower((string) ($imageFile->guessExtension() ?: 'jpg'));

        if (!in_array($safeExtension, ['jpg', 'jpeg', 'png', 'webp', 'svg'], true)) {
            $safeExtension = 'jpg';
        }

        $filename = $baseFilename . '.' . $safeExtension;
        $storedPath = $imageFile->storeAs($folder, $filename, 'public');

        if (!is_string($storedPath) || $storedPath === '' || !Storage::disk('public')->exists($storedPath)) {
            throw new RuntimeException('Unable to store uploaded image on public disk.');
        }

        return [
            'path' => $storedPath,
            'url' => $this->buildPublicUrl($request, $storedPath),
        ];
    }

    private function buildPublicUrl(Request $request, string $publicPath): string
    {
        return rtrim($request->getSchemeAndHttpHost(), '/') . Storage::url($publicPath);
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
