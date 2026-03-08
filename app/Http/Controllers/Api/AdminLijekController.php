<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lijek;
use App\Models\LijekFondZapis;
use App\Models\LijekRegistarZapis;
use App\Support\LijekCacheVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class AdminLijekController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:120',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 25);

        $query = Lijek::query()
            ->select([
                'id',
                'lijek_id',
                'slug',
                'naziv',
                'naziv_lijeka',
                'brend',
                'atc_sifra',
                'inn',
                'oblik',
                'doza',
                'pakovanje',
                'opis',
                'proizvodjac',
                'nosilac_dozvole',
                'oblik_registar',
                'jacina',
                'pakovanje_registar',
                'broj_dozvole',
                'tip_lijeka',
                'podtip_lijeka',
                'jidl',
                'vazi_od',
                'vazi_do',
                'datum_rjesenja',
                'rezim_izdavanja',
                'posebne_oznake',
                'nalaz_prve_serije',
                'nalaz_prve_serije_prethodno_rjesenje',
                'farmaceutski_oblik',
                'vrsta_lijeka',
                'lista_rfzo_pojasnjenje',
                'aktuelna_cijena',
                'aktuelni_procenat_participacije',
                'aktuelni_iznos_participacije',
                'aktuelna_lista_id',
                'aktuelni_broj_indikacija',
                'updated_at',
            ]);

        if (!empty($validated['search'])) {
            $query->search($validated['search']);
        }

        $lijekovi = $query
            ->orderByRaw("COALESCE(naziv, naziv_lijeka, '') ASC")
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $lijekovi,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $lijek = Lijek::query()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'opis' => 'nullable|string',
            'inn' => 'nullable|string|max:255',
            'jidl' => 'nullable|string|max:64',
            'naziv_lijeka' => 'nullable|string|max:255',
            'proizvodjac' => 'nullable|string|max:255',
            'nosilac_dozvole' => 'nullable|string|max:255',
            'oblik_registar' => 'nullable|string|max:255',
            'jacina' => 'nullable|string|max:255',
            'pakovanje_registar' => 'nullable|string|max:255',
            'broj_dozvole' => 'nullable|string|max:128',
            'tip_lijeka' => 'nullable|string|max:128',
            'podtip_lijeka' => 'nullable|string|max:128',
            'vazi_od' => 'nullable|date',
            'vazi_do' => 'nullable|date',
            'datum_rjesenja' => 'nullable|date',
            'rezim_izdavanja' => 'nullable|string|max:128',
            'posebne_oznake' => 'nullable|string',
            'nalaz_prve_serije' => 'nullable|string',
            'nalaz_prve_serije_prethodno_rjesenje' => 'nullable|string',
            'farmaceutski_oblik' => 'nullable|string|max:255',
            'vrsta_lijeka' => 'nullable|string|max:128',
            'lista_rfzo_pojasnjenje' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $lijek->fill($validator->validated());
        $lijek->save();
        LijekCacheVersion::bump();

        return response()->json([
            'success' => true,
            'message' => 'Lijek je uspjesno azuriran.',
            'data' => $lijek,
        ]);
    }

    public function importXml(Request $request): JsonResponse
    {
        if (!function_exists('simplexml_load_file')) {
            return response()->json([
                'success' => false,
                'message' => 'SimpleXML nije dostupan na serveru (php-xml).',
            ], 500);
        }

        $useDefault = $this->normalizeBooleanInput($request->input('use_default')) === true;
        $absolutePath = null;
        $isTemporaryUpload = false;

        if ($useDefault) {
            $absolutePath = public_path('cjenovnik-lijekova_wf.xml');
            if (!is_file($absolutePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Podrazumijevani XML fajl nije pronadjen na serveru.',
                    'expected_path' => $absolutePath,
                ], 422);
            }
        } else {
            if ($uploadIssue = $this->detectUploadIssue('xml_file')) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'xml_file' => [$uploadIssue],
                    ],
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'xml_file' => 'required|file|uploaded|max:102400',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $uploadedFile = $request->file('xml_file');
            $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
            if ($extension !== 'xml') {
                return response()->json([
                    'success' => false,
                    'message' => 'Odabrani fajl mora biti XML.',
                ], 422);
            }

            $tempFilename = 'lijekovi-import-' . now()->format('Ymd_His') . '-' . Str::random(8) . '.xml';
            $storedPath = $uploadedFile->storeAs('tmp', $tempFilename);

            if (!$storedPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Neuspjelo privremeno cuvanje XML fajla.',
                ], 500);
            }

            $absolutePath = storage_path('app/' . $storedPath);
            $isTemporaryUpload = true;
        }

        try {
            $exitCode = Artisan::call('lijekovi:import', [
                '--path' => $absolutePath,
            ]);

            $output = trim((string) Artisan::output());
            if ($exitCode !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import XML fajla nije uspio.',
                    'command_output' => $this->tailOutput($output),
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'XML import uspjesno zavrsen.',
                'lijekovi_total' => Lijek::query()->count(),
                'fond_zapisi_total' => LijekFondZapis::query()->count(),
                'command_output' => $this->tailOutput($output),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Greska pri pokretanju importa.',
                'error' => $e->getMessage(),
            ], 500);
        } finally {
            if ($isTemporaryUpload && $absolutePath !== null && is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }

    public function importRegistar(Request $request): JsonResponse
    {
        if ($uploadIssue = $this->detectUploadIssue('registar_file')) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'registar_file' => [$uploadIssue],
                ],
            ], 422);
        }

        $request->merge([
            'truncate' => $this->normalizeBooleanInput($request->input('truncate')),
            'allow_overwrite' => $this->normalizeBooleanInput($request->input('allow_overwrite')),
        ]);

        $validator = Validator::make($request->all(), [
            'registar_file' => 'required|file|uploaded|max:102400',
            'truncate' => 'nullable|boolean',
            'allow_overwrite' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedFile = $request->file('registar_file');
        $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'xml'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Podrzani su samo CSV i XML fajlovi.',
            ], 422);
        }

        $tempFilename = 'lijekovi-registar-' . now()->format('Ymd_His') . '-' . Str::random(8) . '.' . $extension;
        $storedPath = $uploadedFile->storeAs('tmp', $tempFilename);

        if (!$storedPath) {
            return response()->json([
                'success' => false,
                'message' => 'Neuspjelo privremeno cuvanje fajla.',
            ], 500);
        }

        $absolutePath = storage_path('app/' . $storedPath);

        try {
            $args = [
                '--path' => $absolutePath,
                '--json' => true,
            ];

            if ($request->boolean('truncate')) {
                $args['--truncate'] = true;
            }

            if ($request->boolean('allow_overwrite')) {
                $args['--allow-overwrite'] = true;
            }

            $exitCode = Artisan::call('lijekovi:import-registar', $args);
            $output = trim((string) Artisan::output());

            if ($exitCode !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registar import nije uspio.',
                    'command_output' => $this->tailOutput($output),
                ], 500);
            }

            $summary = json_decode($output, true);
            if (!is_array($summary)) {
                $summary = [
                    'command_output' => $this->tailOutput($output),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Registar import uspjesno zavrsen.',
                'summary' => $summary,
                'registar_rows_total' => LijekRegistarZapis::query()->count(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Greska pri pokretanju registar importa.',
                'error' => $e->getMessage(),
            ], 500);
        } finally {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }

    public function qualityAudit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:500',
        ]);

        $limit = (int) ($validated['limit'] ?? 50);

        try {
            $exitCode = Artisan::call('lijekovi:audit-quality', [
                '--json' => true,
                '--limit' => $limit,
            ]);

            $output = trim((string) Artisan::output());
            if ($exitCode !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Audit nije uspio.',
                    'command_output' => $this->tailOutput($output),
                ], 500);
            }

            $audit = json_decode($output, true);
            if (!is_array($audit)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Audit je zavrsen, ali JSON rezultat nije validan.',
                    'command_output' => $this->tailOutput($output),
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $audit,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Greska pri pokretanju audita.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function tailOutput(string $output, int $maxLines = 20): array
    {
        if ($output === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $output) ?: [];
        $lines = array_values(array_filter(array_map(static fn ($line) => trim((string) $line), $lines), static fn ($line) => $line !== ''));

        if (count($lines) <= $maxLines) {
            return $lines;
        }

        return array_slice($lines, -$maxLines);
    }

    private function normalizeBooleanInput(mixed $value): mixed
    {
        if ($value === null || is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            if ($value === 1) {
                return true;
            }
            if ($value === 0) {
                return false;
            }
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return $value;
    }

    private function detectUploadIssue(string $field): ?string
    {
        if (!isset($_FILES[$field])) {
            return null;
        }

        $errorCode = (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_OK);
        if ($errorCode === UPLOAD_ERR_OK || $errorCode === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $baseMessage = match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'Upload nije uspio jer je fajl veci od server limita (php.ini).',
            UPLOAD_ERR_FORM_SIZE => 'Upload nije uspio jer je fajl veci od dozvoljene velicine forme.',
            UPLOAD_ERR_PARTIAL => 'Upload nije zavrsen (fajl je poslan samo djelimicno).',
            UPLOAD_ERR_NO_TMP_DIR => 'Upload nije uspio jer server nema privremeni folder za upload.',
            UPLOAD_ERR_CANT_WRITE => 'Upload nije uspio jer server ne moze upisati fajl na disk.',
            UPLOAD_ERR_EXTENSION => 'Upload je zaustavljen od PHP ekstenzije na serveru.',
            default => 'Upload fajla nije uspio zbog nepoznate server greske.',
        };

        return $baseMessage . ' upload_max_filesize=' . ini_get('upload_max_filesize') . ', post_max_size=' . ini_get('post_max_size') . '.';
    }
}
