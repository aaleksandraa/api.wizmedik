<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specijalnost;
use App\Models\SpecialtyServicePage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SpecialtyServicePageController extends Controller
{
    public function showBySlugs(string $specialtySlug, string $serviceSlug): JsonResponse
    {
        $page = SpecialtyServicePage::query()
            ->published()
            ->where('slug', $serviceSlug)
            ->whereHas('specialty', function ($query) use ($specialtySlug) {
                $query->where('slug', $specialtySlug)->where('aktivan', true);
            })
            ->with(['specialty:id,naziv,slug'])
            ->first();

        if (!$page) {
            return response()->json([
                'message' => 'Stranica usluge nije pronađena.',
            ], 404);
        }

        return response()->json($this->transformPage($page));
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $query = SpecialtyServicePage::query()
            ->with(['specialty:id,naziv,slug'])
            ->orderByDesc('updated_at');

        if ($request->filled('specialty_id')) {
            $query->where('specialty_id', (int) $request->input('specialty_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $term = mb_strtolower($search);
                $q->whereRaw('LOWER(naziv) LIKE ?', ["%{$term}%"])
                    ->orWhereRaw('LOWER(slug) LIKE ?', ["%{$term}%"])
                    ->orWhereRaw('LOWER(COALESCE(meta_title, \'\')) LIKE ?', ["%{$term}%"]);
            });
        }

        $pages = $query->get()->map(fn (SpecialtyServicePage $page) => $this->transformPage($page));

        return response()->json($pages);
    }

    public function adminShow(int $id): JsonResponse
    {
        $page = SpecialtyServicePage::with(['specialty:id,naziv,slug'])->findOrFail($id);
        return response()->json($this->transformPage($page));
    }

    public function adminStore(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $slug = $this->ensureUniqueSlug(
            specialtyId: (int) $validated['specialty_id'],
            desiredSlug: $validated['slug'] ?? null,
            naziv: $validated['naziv']
        );

        $page = SpecialtyServicePage::create([
            ...$validated,
            'slug' => $slug,
            'created_by' => auth()->id(),
            'published_at' => ($validated['status'] ?? 'draft') === 'published'
                ? now()
                : null,
        ]);

        $this->clearSpecialtyCacheById((int) $page->specialty_id);
        $page->load(['specialty:id,naziv,slug']);

        return response()->json([
            'message' => 'Stranica usluge je uspješno kreirana.',
            'data' => $this->transformPage($page),
        ], 201);
    }

    public function adminUpdate(Request $request, int $id): JsonResponse
    {
        $page = SpecialtyServicePage::findOrFail($id);
        $originalSpecialtyId = (int) $page->specialty_id;
        $validated = $this->validatePayload($request, true);

        $specialtyId = isset($validated['specialty_id'])
            ? (int) $validated['specialty_id']
            : (int) $page->specialty_id;

        $naziv = $validated['naziv'] ?? $page->naziv;
        $desiredSlug = array_key_exists('slug', $validated) ? $validated['slug'] : $page->slug;

        $validated['slug'] = $this->ensureUniqueSlug(
            specialtyId: $specialtyId,
            desiredSlug: $desiredSlug,
            naziv: $naziv,
            ignoreId: $page->id
        );

        if (array_key_exists('status', $validated)) {
            if ($validated['status'] === 'published' && !$page->published_at) {
                $validated['published_at'] = now();
            }

            if ($validated['status'] !== 'published') {
                $validated['published_at'] = null;
            }
        }

        $page->update($validated);
        $page->refresh()->load(['specialty:id,naziv,slug']);
        $this->clearSpecialtyCacheById($originalSpecialtyId);
        $this->clearSpecialtyCacheById((int) $page->specialty_id);

        return response()->json([
            'message' => 'Stranica usluge je uspješno ažurirana.',
            'data' => $this->transformPage($page),
        ]);
    }

    public function adminDestroy(int $id): JsonResponse
    {
        $page = SpecialtyServicePage::findOrFail($id);
        $specialtyId = (int) $page->specialty_id;
        $page->delete();
        $this->clearSpecialtyCacheById($specialtyId);

        return response()->json([
            'message' => 'Stranica usluge je obrisana.',
        ]);
    }

    private function validatePayload(Request $request, bool $isUpdate = false): array
    {
        $prefix = $isUpdate ? 'sometimes|' : '';

        $data = $request->all();

        foreach ([
            'naziv',
            'slug',
            'kratki_opis',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'canonical_url',
            'og_image',
        ] as $field) {
            if (array_key_exists($field, $data) && is_string($data[$field])) {
                $trimmed = trim($data[$field]);
                $data[$field] = $trimmed === '' ? null : $trimmed;
            }
        }

        if (array_key_exists('status', $data) && is_string($data['status'])) {
            $data['status'] = mb_strtolower(trim($data['status']));
        }

        if (array_key_exists('specialty_id', $data)) {
            $data['specialty_id'] = is_numeric($data['specialty_id'])
                ? (int) $data['specialty_id']
                : 0;
        }

        if ($isUpdate) {
            if (array_key_exists('specialty_id', $data)) {
                if ($data['specialty_id'] <= 0 || !Specijalnost::whereKey($data['specialty_id'])->exists()) {
                    unset($data['specialty_id']);
                }
            }
        } else {
            if (
                !array_key_exists('specialty_id', $data)
                || $data['specialty_id'] <= 0
                || !Specijalnost::whereKey($data['specialty_id'])->exists()
            ) {
                $fallbackSpecialtyId = Specijalnost::query()->where('aktivan', true)->value('id')
                    ?? Specijalnost::query()->value('id');
                if ($fallbackSpecialtyId) {
                    $data['specialty_id'] = (int) $fallbackSpecialtyId;
                }
            }
        }

        if ($isUpdate) {
            if (array_key_exists('status', $data) && !in_array($data['status'], ['draft', 'published'], true)) {
                unset($data['status']);
            }
        } else {
            if (!array_key_exists('status', $data) || !in_array($data['status'], ['draft', 'published'], true)) {
                $data['status'] = 'draft';
            }
        }

        if (array_key_exists('is_indexable', $data)) {
            $normalizedBool = filter_var($data['is_indexable'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($normalizedBool !== null) {
                $data['is_indexable'] = $normalizedBool;
            }
        }

        if (array_key_exists('sort_order', $data) && ($data['sort_order'] === '' || $data['sort_order'] === null)) {
            $data['sort_order'] = 0;
        }
        if (array_key_exists('sort_order', $data) && is_numeric($data['sort_order'])) {
            $data['sort_order'] = max(0, (int) $data['sort_order']);
        }

        $data['naziv'] = $this->normalizeMaxString($data['naziv'] ?? null, 255);
        $data['slug'] = $this->normalizeMaxString($data['slug'] ?? null, 255);
        $data['kratki_opis'] = $this->normalizeMaxString($data['kratki_opis'] ?? null, 1000);
        $data['meta_title'] = $this->normalizeMaxString($data['meta_title'] ?? null, 70);
        $data['meta_description'] = $this->normalizeMaxString($data['meta_description'] ?? null, 160);
        $data['meta_keywords'] = $this->normalizeMaxString($data['meta_keywords'] ?? null, 255);

        if (array_key_exists('sadrzaj', $data) && is_string($data['sadrzaj'])) {
            $data['sadrzaj'] = trim($data['sadrzaj']) === '' ? null : $data['sadrzaj'];
        }

        if (array_key_exists('canonical_url', $data)) {
            $data['canonical_url'] = $this->normalizeOptionalUrl($data['canonical_url'] ?? null);
        }

        if (array_key_exists('og_image', $data)) {
            $data['og_image'] = $this->normalizeOptionalUrl($data['og_image'] ?? null);
        }

        $validated = validator($data, [
            'specialty_id' => $prefix . 'required|integer|min:1',
            'naziv' => $prefix . 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'kratki_opis' => 'nullable|string|max:1000',
            'sadrzaj' => 'nullable|string',
            'status' => $prefix . 'required|in:draft,published',
            'is_indexable' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|string|max:2048',
            'og_image' => 'nullable|string|max:2048',
        ])->validate();

        if (array_key_exists('slug', $validated)) {
            $validated['slug'] = trim((string) $validated['slug']);
            if ($validated['slug'] === '') {
                $validated['slug'] = null;
            }
        }

        return $validated;
    }

    private function normalizeMaxString(mixed $value, int $maxLength): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $text = trim($value);
        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, $maxLength);
    }

    private function normalizeOptionalUrl(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $url = trim($value);
        if ($url === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        if (preg_match('/^[^\/\s]+\.[^\/\s]+/', $url)) {
            return 'https://' . $url;
        }

        if (Str::startsWith($url, '/')) {
            $baseUrl = rtrim((string) config('app.frontend_url', config('app.url', 'https://wizmedik.com')), '/');
            return $baseUrl . '/' . ltrim($url, '/');
        }

        return null;
    }

    private function ensureUniqueSlug(
        int $specialtyId,
        ?string $desiredSlug,
        string $naziv,
        ?int $ignoreId = null
    ): string {
        $base = Str::slug($desiredSlug ?: $naziv);
        if ($base === '') {
            $base = 'usluga';
        }

        $slug = $base;
        $counter = 2;

        while ($this->slugExists($specialtyId, $slug, $ignoreId)) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function slugExists(int $specialtyId, string $slug, ?int $ignoreId = null): bool
    {
        $query = SpecialtyServicePage::query()
            ->withTrashed()
            ->where('specialty_id', $specialtyId)
            ->where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    private function clearSpecialtyCacheById(int $specialtyId): void
    {
        if ($specialtyId <= 0) {
            return;
        }

        $slug = \DB::table('specijalnosti')
            ->where('id', $specialtyId)
            ->value('slug');

        if (!empty($slug)) {
            \Cache::forget("specialty:{$slug}");
        }
    }

    private function transformPage(SpecialtyServicePage $page): array
    {
        $specialtySlug = $page->specialty?->slug ?? '';
        $urlPath = $specialtySlug !== ''
            ? "specijalnost/{$specialtySlug}/{$page->slug}"
            : "specijalnost/{$page->slug}";

        return [
            'id' => $page->id,
            'specialty_id' => $page->specialty_id,
            'naziv' => $page->naziv,
            'slug' => $page->slug,
            'kratki_opis' => $page->kratki_opis,
            'sadrzaj' => $page->sadrzaj,
            'status' => $page->status,
            'is_indexable' => (bool) $page->is_indexable,
            'sort_order' => (int) $page->sort_order,
            'published_at' => optional($page->published_at)->toIso8601String(),
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'meta_keywords' => $page->meta_keywords,
            'canonical_url' => $page->canonical_url,
            'og_image' => $page->og_image,
            'specialty' => $page->specialty ? [
                'id' => $page->specialty->id,
                'naziv' => $page->specialty->naziv,
                'slug' => $page->specialty->slug,
            ] : null,
            'url_path' => $urlPath,
            'url' => rtrim(config('app.frontend_url', 'https://wizmedik.com'), '/') . '/' . $urlPath,
            'created_at' => optional($page->created_at)->toIso8601String(),
            'updated_at' => optional($page->updated_at)->toIso8601String(),
        ];
    }
}
