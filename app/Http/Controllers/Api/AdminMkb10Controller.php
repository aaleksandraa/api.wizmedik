<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mkb10Kategorija;
use App\Models\Mkb10Podkategorija;
use App\Models\Mkb10Dijagnoza;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AdminMkb10Controller extends Controller
{
    // ==================== KATEGORIJE ====================

    public function indexKategorije(): JsonResponse
    {
        try {
            $kategorije = Mkb10Kategorija::ordered()
                ->withCount(['dijagnoze', 'podkategorije'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $kategorije
            ]);
        } catch (\Exception $e) {
            Log::error('MKB10 kategorije error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Greška pri učitavanju kategorija: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeKategorija(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kod_od' => 'required|string|max:10',
            'kod_do' => 'required|string|max:10',
            'naziv' => 'required|string|max:500',
            'opis' => 'nullable|string',
            'boja' => 'nullable|string|max:20',
            'ikona' => 'nullable|string|max:50',
            'redoslijed' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $kategorija = Mkb10Kategorija::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Kategorija uspješno kreirana',
            'data' => $kategorija
        ], 201);
    }

    public function updateKategorija(Request $request, int $id): JsonResponse
    {
        $kategorija = Mkb10Kategorija::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'kod_od' => 'string|max:10',
            'kod_do' => 'string|max:10',
            'naziv' => 'string|max:500',
            'opis' => 'nullable|string',
            'boja' => 'nullable|string|max:20',
            'ikona' => 'nullable|string|max:50',
            'redoslijed' => 'nullable|integer',
            'aktivan' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $kategorija->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Kategorija uspješno ažurirana',
            'data' => $kategorija
        ]);
    }

    public function destroyKategorija(int $id): JsonResponse
    {
        $kategorija = Mkb10Kategorija::findOrFail($id);
        $kategorija->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategorija uspješno obrisana'
        ]);
    }

    // ==================== PODKATEGORIJE ====================

    public function indexPodkategorije(int $kategorijaId): JsonResponse
    {
        $podkategorije = Mkb10Podkategorija::where('kategorija_id', $kategorijaId)
            ->ordered()
            ->withCount('dijagnoze')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $podkategorije
        ]);
    }

    public function storePodkategorija(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kategorija_id' => 'required|exists:mkb10_kategorije,id',
            'kod_od' => 'required|string|max:10',
            'kod_do' => 'required|string|max:10',
            'naziv' => 'required|string|max:500',
            'opis' => 'nullable|string',
            'redoslijed' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $podkategorija = Mkb10Podkategorija::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Podkategorija uspješno kreirana',
            'data' => $podkategorija
        ], 201);
    }

    public function updatePodkategorija(Request $request, int $id): JsonResponse
    {
        $podkategorija = Mkb10Podkategorija::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'kategorija_id' => 'exists:mkb10_kategorije,id',
            'kod_od' => 'string|max:10',
            'kod_do' => 'string|max:10',
            'naziv' => 'string|max:500',
            'opis' => 'nullable|string',
            'redoslijed' => 'nullable|integer',
            'aktivan' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $podkategorija->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Podkategorija uspješno ažurirana',
            'data' => $podkategorija
        ]);
    }

    public function destroyPodkategorija(int $id): JsonResponse
    {
        $podkategorija = Mkb10Podkategorija::findOrFail($id);
        $podkategorija->delete();

        return response()->json([
            'success' => true,
            'message' => 'Podkategorija uspješno obrisana'
        ]);
    }

    // ==================== DIJAGNOZE ====================

    public function indexDijagnoze(Request $request): JsonResponse
    {
        try {
            $query = Mkb10Dijagnoza::with(['kategorija:id,kod_od,naziv', 'podkategorija:id,kod_od,naziv']);

            if ($request->has('kategorija_id') && $request->kategorija_id) {
                $query->where('kategorija_id', $request->kategorija_id);
            }

            if ($request->has('podkategorija_id') && $request->podkategorija_id) {
                $query->where('podkategorija_id', $request->podkategorija_id);
            }

            if ($request->has('search') && strlen($request->search) >= 2) {
                $query->search($request->search);
            }

            $dijagnoze = $query->orderBy('kod')
                ->paginate($request->get('per_page', 100));

            return response()->json([
                'success' => true,
                'data' => $dijagnoze
            ]);
        } catch (\Exception $e) {
            Log::error('MKB10 dijagnoze error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Greška pri učitavanju dijagnoza: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeDijagnoza(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kategorija_id' => 'required|exists:mkb10_kategorije,id',
            'podkategorija_id' => 'nullable|exists:mkb10_podkategorije,id',
            'kod' => 'required|string|max:10|unique:mkb10_dijagnoze,kod',
            'naziv' => 'required|string|max:500',
            'naziv_lat' => 'nullable|string|max:500',
            'opis' => 'nullable|string',
            'ukljucuje' => 'nullable|string',
            'iskljucuje' => 'nullable|string',
            'sinonimi' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $dijagnoza = Mkb10Dijagnoza::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Dijagnoza uspješno kreirana',
            'data' => $dijagnoza
        ], 201);
    }

    public function updateDijagnoza(Request $request, int $id): JsonResponse
    {
        $dijagnoza = Mkb10Dijagnoza::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'kategorija_id' => 'exists:mkb10_kategorije,id',
            'podkategorija_id' => 'nullable|exists:mkb10_podkategorije,id',
            'kod' => 'string|max:10|unique:mkb10_dijagnoze,kod,' . $id,
            'naziv' => 'string|max:500',
            'naziv_lat' => 'nullable|string|max:500',
            'opis' => 'nullable|string',
            'ukljucuje' => 'nullable|string',
            'iskljucuje' => 'nullable|string',
            'sinonimi' => 'nullable|array',
            'aktivan' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $dijagnoza->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Dijagnoza uspješno ažurirana',
            'data' => $dijagnoza
        ]);
    }

    public function destroyDijagnoza(int $id): JsonResponse
    {
        $dijagnoza = Mkb10Dijagnoza::findOrFail($id);
        $dijagnoza->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dijagnoza uspješno obrisana'
        ]);
    }

    // ==================== IMPORT ====================

    /**
     * Import kategorija iz CSV/JSON
     */
    public function importKategorije(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt,json|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $content = file_get_contents($file->getRealPath());

            $imported = 0;
            $errors = [];

            if ($extension === 'json') {
                $data = json_decode($content, true);
                foreach ($data as $index => $item) {
                    try {
                        Mkb10Kategorija::updateOrCreate(
                            ['kod_od' => $item['kod_od'], 'kod_do' => $item['kod_do']],
                            [
                                'naziv' => $item['naziv'],
                                'opis' => $item['opis'] ?? null,
                                'boja' => $item['boja'] ?? null,
                                'ikona' => $item['ikona'] ?? null,
                                'redoslijed' => $item['redoslijed'] ?? $index,
                            ]
                        );
                        $imported++;
                    } catch (\Exception $e) {
                        $errors[] = "Red {$index}: " . $e->getMessage();
                    }
                }
            } else {
                // CSV format: kod_od;kod_do;naziv;opis
                $lines = explode("\n", $content);
                foreach ($lines as $index => $line) {
                    if ($index === 0 || empty(trim($line))) continue; // Skip header

                    $parts = str_getcsv($line, ';');
                    if (count($parts) >= 3) {
                        try {
                            Mkb10Kategorija::updateOrCreate(
                                ['kod_od' => trim($parts[0]), 'kod_do' => trim($parts[1])],
                                [
                                    'naziv' => trim($parts[2]),
                                    'opis' => $parts[3] ?? null,
                                    'redoslijed' => $index,
                                ]
                            );
                            $imported++;
                        } catch (\Exception $e) {
                            $errors[] = "Red {$index}: " . $e->getMessage();
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Uspješno importovano {$imported} kategorija",
                'imported' => $imported,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('MKB10 import error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Greška pri importu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import dijagnoza iz CSV/JSON
     */
    public function importDijagnoze(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt,json|max:51200', // 50MB max
            'kategorija_id' => 'nullable|exists:mkb10_kategorije,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $content = file_get_contents($file->getRealPath());

            $imported = 0;
            $skipped = 0;
            $errors = [];

            DB::beginTransaction();

            if ($extension === 'json') {
                $data = json_decode($content, true);
                foreach ($data as $index => $item) {
                    try {
                        // Pronađi kategoriju po kodu
                        $kategorija = $this->findKategorijaByKod($item['kod']);
                        if (!$kategorija) {
                            $errors[] = "Red {$index}: Kategorija nije pronađena za kod {$item['kod']}";
                            $skipped++;
                            continue;
                        }

                        Mkb10Dijagnoza::updateOrCreate(
                            ['kod' => $item['kod']],
                            [
                                'kategorija_id' => $kategorija->id,
                                'podkategorija_id' => $item['podkategorija_id'] ?? null,
                                'naziv' => $item['naziv'],
                                'naziv_lat' => $item['naziv_lat'] ?? null,
                                'opis' => $item['opis'] ?? null,
                                'ukljucuje' => $item['ukljucuje'] ?? null,
                                'iskljucuje' => $item['iskljucuje'] ?? null,
                                'sinonimi' => $item['sinonimi'] ?? null,
                            ]
                        );
                        $imported++;
                    } catch (\Exception $e) {
                        $errors[] = "Red {$index}: " . $e->getMessage();
                        $skipped++;
                    }
                }
            } else {
                // CSV format: kod;naziv;naziv_lat;opis
                $lines = explode("\n", $content);
                foreach ($lines as $index => $line) {
                    if ($index === 0 || empty(trim($line))) continue;

                    $parts = str_getcsv($line, ';');
                    if (count($parts) >= 2) {
                        try {
                            $kod = trim($parts[0]);
                            $kategorija = $request->kategorija_id
                                ? Mkb10Kategorija::find($request->kategorija_id)
                                : $this->findKategorijaByKod($kod);

                            if (!$kategorija) {
                                $skipped++;
                                continue;
                            }

                            Mkb10Dijagnoza::updateOrCreate(
                                ['kod' => $kod],
                                [
                                    'kategorija_id' => $kategorija->id,
                                    'naziv' => trim($parts[1]),
                                    'naziv_lat' => isset($parts[2]) ? trim($parts[2]) : null,
                                    'opis' => isset($parts[3]) ? trim($parts[3]) : null,
                                ]
                            );
                            $imported++;
                        } catch (\Exception $e) {
                            $errors[] = "Red {$index}: " . $e->getMessage();
                            $skipped++;
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Uspješno importovano {$imported} dijagnoza, preskočeno {$skipped}",
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => array_slice($errors, 0, 20) // Limit errors shown
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MKB10 dijagnoze import error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Greška pri importu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pronađi kategoriju na osnovu koda dijagnoze
     */
    private function findKategorijaByKod(string $kod): ?Mkb10Kategorija
    {
        $letter = strtoupper(substr($kod, 0, 1));

        return Mkb10Kategorija::where('kod_od', '<=', $letter . '99')
            ->where('kod_do', '>=', $letter . '00')
            ->orWhere(function($q) use ($letter) {
                $q->whereRaw("SUBSTRING(kod_od, 1, 1) = ?", [$letter]);
            })
            ->first();
    }

    /**
     * Export kategorija
     */
    public function exportKategorije(): JsonResponse
    {
        $kategorije = Mkb10Kategorija::ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $kategorije
        ]);
    }

    /**
     * Export dijagnoza
     */
    public function exportDijagnoze(Request $request): JsonResponse
    {
        $query = Mkb10Dijagnoza::with(['kategorija:id,kod_od,naziv']);

        if ($request->has('kategorija_id')) {
            $query->where('kategorija_id', $request->kategorija_id);
        }

        $dijagnoze = $query->orderBy('kod')->get();

        return response()->json([
            'success' => true,
            'data' => $dijagnoze
        ]);
    }

    // ==================== SETTINGS ====================

    /**
     * Dohvati MKB-10 postavke
     */
    public function getSettings(): JsonResponse
    {
        try {
            // Direktno čitaj iz baze bez keša
            $setting = \App\Models\SiteSetting::where('key', 'mkb10_show_category_name_in_tabs')->first();
            $value = $setting ? $setting->value === 'true' : true;

            return response()->json([
                'success' => true,
                'data' => [
                    'show_category_name_in_tabs' => $value,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('MKB10 settings error: ' . $e->getMessage());
            return response()->json([
                'success' => true,
                'data' => [
                    'show_category_name_in_tabs' => true,
                ]
            ]);
        }
    }

    /**
     * Ažuriraj MKB-10 postavke
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'show_category_name_in_tabs' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $value = $request->show_category_name_in_tabs ? 'true' : 'false';

        // Direktno ažuriraj u bazi i obriši keš
        \App\Models\SiteSetting::updateOrCreate(
            ['key' => 'mkb10_show_category_name_in_tabs'],
            ['value' => $value]
        );

        // Obriši keš
        \Illuminate\Support\Facades\Cache::forget('setting_mkb10_show_category_name_in_tabs');

        Log::info('MKB10 settings updated', [
            'show_category_name_in_tabs' => $value,
            'request_value' => $request->show_category_name_in_tabs
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Postavke uspješno ažurirane',
            'data' => [
                'show_category_name_in_tabs' => $request->show_category_name_in_tabs
            ]
        ]);
    }
}
