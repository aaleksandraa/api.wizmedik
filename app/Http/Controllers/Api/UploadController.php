<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use enshrined\svgSanitize\Sanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Laravel\Facades\Image;
use RuntimeException;

class UploadController extends Controller
{
    /**
     * Folders the upload/delete endpoints are allowed to write to and remove from.
     * Used both for validation and to constrain deletions to image folders only.
     */
    private const ALLOWED_FOLDERS = [
        'doctors', 'clinics', 'cities', 'covers', 'blog', 'laboratories',
        'spas', 'logos', 'backgrounds', 'pharmacies', 'care-homes',
    ];

    public function uploadImage(Request $request)
    {
        try {
            $request->validate([
                // Use file-based validation instead of Laravel's image rule because
                // modern mobile formats like HEIC/HEIF/AVIF can fail the generic image
                // validator even though they are legitimate user uploads.
                'image'  => 'required|file|max:5120', // 5MB max
                'folder' => 'required|in:' . implode(',', self::ALLOWED_FOLDERS),
            ]);

            $folder = $request->folder;
            $imageFile = $request->file('image');
            $extension = strtolower((string) $imageFile->getClientOriginalExtension());
            $baseFilename = time() . '_' . uniqid();

            // Security: Verify it's actually an image
            $mimeType = $imageFile->getMimeType();

            // Hard block obviously dangerous content types regardless of extension,
            // so a script/HTML file renamed to .jpg can never be stored.
            $blockedMimeTypes = [
                'text/html', 'application/xhtml+xml', 'text/xml', 'application/xml',
                'application/javascript', 'text/javascript',
                'application/x-php', 'application/x-httpd-php', 'text/x-php',
                'application/x-msdownload', 'application/x-sh',
            ];
            if (
                in_array($mimeType, $blockedMimeTypes, true)
                || str_starts_with((string) $mimeType, 'text/')
            ) {
                return response()->json([
                    'message' => 'Odabrani fajl nije podržana slika.',
                    'errors' => [
                        'image' => ['Podržani formati su JPG, PNG, WEBP, SVG, HEIC, HEIF i AVIF.'],
                    ],
                ], 422);
            }

            $allowedMimes = [
                'image/jpeg',
                'image/png',
                'image/jpg',
                'image/pjpeg',
                'image/x-png',
                'image/webp',
                'image/svg+xml',
                'image/heic',
                'image/heif',
                'image/avif',
                'image/avif-sequence',
            ];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'heic', 'heif', 'avif'];

            if (!in_array($mimeType, $allowedMimes, true) && !in_array($extension, $allowedExtensions, true)) {
                return response()->json([
                    'message' => 'Odabrani fajl nije podržana slika.',
                    'errors' => [
                        'image' => ['Podržani formati su JPG, PNG, WEBP, SVG, HEIC, HEIF i AVIF.'],
                    ],
                ], 422);
            }

            // Security: Check file size again (double check)
            if ($imageFile->getSize() > 5242880) { // 5MB in bytes
                return response()->json([
                    'message' => 'Slika je prevelika.',
                    'errors' => [
                        'image' => ['Maksimalna veličina slike je 5 MB.'],
                    ],
                ], 422);
            }

            // SVG: sanitize before storing to neutralize embedded scripts/XSS.
            // We never store raw SVG bytes from an upload on the public disk.
            if ($extension === 'svg' || $mimeType === 'image/svg+xml') {
                $rawSvg = (string) file_get_contents($imageFile->getRealPath());
                $cleanSvg = $this->sanitizeSvg($rawSvg);

                if ($cleanSvg === null) {
                    return response()->json([
                        'message' => 'SVG datoteka nije ispravna ili sadrži nedozvoljen sadržaj.',
                        'errors' => [
                            'image' => ['SVG datoteka nije mogla biti sigurno obrađena.'],
                        ],
                    ], 422);
                }

                $publicPath = "{$folder}/{$baseFilename}.svg";
                Storage::disk('public')->put($publicPath, $cleanSvg);

                $url = $this->buildPublicUrl($request, $publicPath);

                return response()->json([
                    'message' => 'Image uploaded successfully',
                    'url'     => $url,
                    'path'    => $publicPath,
                ]);
            }

            // Security: Verify image dimensions (prevent decompression bombs)
            $shouldValidateDimensions = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)
                || in_array($mimeType, ['image/jpeg', 'image/png', 'image/jpg', 'image/pjpeg', 'image/x-png', 'image/webp'], true);

            if ($shouldValidateDimensions) {
                try {
                    $imageInfo = getimagesize($imageFile->getRealPath());
                    if ($imageInfo === false) {
                        return response()->json([
                            'message' => 'Odabrani fajl nije validna slika.',
                            'errors' => [
                                'image' => ['Sistem nije mogao pročitati dimenzije slike.'],
                            ],
                        ], 422);
                    }

                    // Prevent extremely large images (max 10000x10000)
                    if ($imageInfo[0] > 10000 || $imageInfo[1] > 10000) {
                        return response()->json([
                            'message' => 'Dimenzije slike su prevelike.',
                            'errors' => [
                                'image' => ['Maksimalne dozvoljene dimenzije su 10000x10000 px.'],
                            ],
                        ], 422);
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'Odabrani fajl nije validna slika.',
                        'errors' => [
                            'image' => ['Sistem nije mogao obraditi odabrani fajl.'],
                        ],
                    ], 422);
                }
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

                    $safeExtension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif', 'avif'], true)
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

        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first() ?: 'Podaci za upload nisu ispravni.',
                'errors' => $e->errors(),
            ], 422);
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
        $safeExtension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'heic', 'heif', 'avif'], true)
            ? $extension
            : strtolower((string) ($imageFile->guessExtension() ?: 'jpg'));

        if (!in_array($safeExtension, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'heic', 'heif', 'avif'], true)) {
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

    /**
     * Sanitize raw SVG markup, stripping scripts, event handlers and external
     * references. Returns null if the input is empty or cannot be sanitized.
     */
    private function sanitizeSvg(string $rawSvg): ?string
    {
        if (trim($rawSvg) === '') {
            return null;
        }

        try {
            $sanitizer = new Sanitizer();
            $sanitizer->removeRemoteReferences(true);
            $clean = $sanitizer->sanitize($rawSvg);

            if (! is_string($clean) || trim($clean) === '') {
                return null;
            }

            return $clean;
        } catch (\Throwable $e) {
            \Log::warning('SVG sanitization failed', ['error' => $e->getMessage()]);
            return null;
        }
    }


    public function deleteImage(Request $request)
    {
        $request->validate([
            'path' => 'required|string|max:255',
        ]);

        $path = (string) $request->input('path');

        // Accept full URLs too (frontend sometimes stores the absolute URL):
        // reduce to the storage-relative path before validating.
        if (str_contains($path, '://')) {
            $parsedPath = parse_url($path, PHP_URL_PATH) ?: '';
            $path = preg_replace('#^/?storage/#', '', ltrim($parsedPath, '/')) ?? '';
        }

        if (! $this->isSafeImagePath($path)) {
            return response()->json([
                'message' => 'Neispravna putanja slike.',
            ], 422);
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return response()->json(['message' => 'Image deleted']);
        }

        return response()->json(['message' => 'Image not found'], 404);
    }

    /**
     * Allow deletion only of image files inside the known upload folders,
     * blocking path traversal and removal of arbitrary files on the disk.
     */
    private function isSafeImagePath(string $path): bool
    {
        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0") || str_contains($path, '\\')) {
            return false;
        }

        if (str_starts_with($path, '/')) {
            return false;
        }

        $folderPattern = implode('|', array_map('preg_quote', self::ALLOWED_FOLDERS));

        // {folder}/{optional-subfolder}/{filename}.{image-ext}
        return (bool) preg_match(
            '#^(' . $folderPattern . ')/(?:[A-Za-z0-9._-]+/)*[A-Za-z0-9._-]+\.(jpg|jpeg|png|webp|svg|heic|heif|avif)$#i',
            $path
        );
    }
}
