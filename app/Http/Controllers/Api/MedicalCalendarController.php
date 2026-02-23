<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MedicalCalendarController extends Controller
{
    // Public API - prikaz kalendara
    public function index(Request $request)
    {
        $query = MedicalCalendar::active()->orderBy('date')->orderBy('sort_order');

        if ($request->has('year')) {
            $query->byYear($request->year);
        }

        if ($request->has('month') && $request->has('year')) {
            $query->byMonth($request->year, $request->month);
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        return response()->json($query->get());
    }

    public function show($id)
    {
        $event = MedicalCalendar::active()->findOrFail($id);
        return response()->json($event);
    }

    // Admin CRUD
    public function adminIndex(Request $request)
    {
        $query = MedicalCalendar::orderBy('date', 'desc')->orderBy('sort_order');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $perPage = $request->get('per_page', 50);
        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:day,week,month,campaign',
            'end_date' => 'nullable|date|after_or_equal:date',
            'category' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
            'sort_order' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $event = MedicalCalendar::create($request->all());
        return response()->json($event, 201);
    }

    public function update(Request $request, $id)
    {
        $event = MedicalCalendar::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|required|date',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:day,week,month,campaign',
            'end_date' => 'nullable|date|after_or_equal:date',
            'category' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
            'sort_order' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $event->update($request->all());
        return response()->json($event);
    }

    public function destroy($id)
    {
        $event = MedicalCalendar::findOrFail($id);
        $event->delete();
        return response()->json(['message' => 'Event deleted successfully']);
    }

    public function bulkDestroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:medical_calendar,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $deleted = MedicalCalendar::whereIn('id', $request->input('ids'))->delete();

        return response()->json([
            'message' => 'Događaji uspješno obrisani.',
            'deleted' => $deleted,
        ]);
    }

    public function importXml(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'xml_file' => 'required|file|mimes:xml|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('xml_file');
        $xmlContent = @file_get_contents($file->getRealPath());

        if ($xmlContent === false || trim($xmlContent) === '') {
            return response()->json([
                'message' => 'XML fajl je prazan ili nečitljiv.',
            ], 422);
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $errors = collect(libxml_get_errors())
                ->map(fn ($error) => trim($error->message))
                ->unique()
                ->values();

            libxml_clear_errors();

            return response()->json([
                'message' => 'Neispravan XML format.',
                'xml_errors' => $errors,
            ], 422);
        }

        libxml_clear_errors();

        $rawEvents = [];
        if (isset($xml->event)) {
            $rawEvents = $xml->event;
        } elseif (isset($xml->item)) {
            $rawEvents = $xml->item;
        } else {
            foreach ($xml->children() as $child) {
                $rawEvents[] = $child;
            }
        }

        if (count($rawEvents) === 0) {
            return response()->json([
                'message' => 'XML ne sadrži nijedan događaj.',
            ], 422);
        }

        $validatedRows = [];
        $rowErrors = [];

        foreach ($rawEvents as $index => $rawEvent) {
            $rowNumber = $index + 1;

            $row = [
                'date' => trim((string) ($rawEvent->date ?? '')),
                'title' => trim((string) ($rawEvent->title ?? '')),
                'description' => trim((string) ($rawEvent->description ?? '')) ?: null,
                'type' => trim((string) ($rawEvent->type ?? '')) ?: 'day',
                'end_date' => trim((string) ($rawEvent->end_date ?? '')) ?: null,
                'category' => trim((string) ($rawEvent->category ?? '')) ?: null,
                'color' => trim((string) ($rawEvent->color ?? '')) ?: '#0891b2',
                'is_active' => $this->parseBoolValue((string) ($rawEvent->is_active ?? '1')),
                'sort_order' => is_numeric((string) ($rawEvent->sort_order ?? ''))
                    ? (int) $rawEvent->sort_order
                    : 0,
            ];

            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $row['color'])) {
                $row['color'] = '#0891b2';
            }

            $rowValidator = Validator::make($row, [
                'date' => 'required|date',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:day,week,month,campaign',
                'end_date' => 'nullable|date|after_or_equal:date',
                'category' => 'nullable|string|max:100',
                'color' => 'nullable|string|max:7',
                'is_active' => 'boolean',
                'sort_order' => 'integer',
            ]);

            if ($rowValidator->fails()) {
                $rowErrors[] = [
                    'row' => $rowNumber,
                    'errors' => $rowValidator->errors(),
                ];
                continue;
            }

            $validatedRows[] = $row;
        }

        if (count($validatedRows) === 0) {
            return response()->json([
                'message' => 'Nijedan red iz XML fajla nije validan.',
                'row_errors' => $rowErrors,
            ], 422);
        }

        $created = 0;
        $updated = 0;

        DB::beginTransaction();
        try {
            foreach ($validatedRows as $row) {
                $existing = MedicalCalendar::whereDate('date', $row['date'])
                    ->where('title', $row['title'])
                    ->where('type', $row['type'])
                    ->first();

                if ($existing) {
                    $existing->update($row);
                    $updated++;
                    continue;
                }

                MedicalCalendar::create($row);
                $created++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Greška pri unosu XML podataka.',
                'error' => $e->getMessage(),
            ], 500);
        }

        $response = [
            'message' => 'XML podaci su uspješno obrađeni.',
            'created' => $created,
            'updated' => $updated,
            'failed' => count($rowErrors),
            'total_processed' => count($rawEvents),
        ];

        if (!empty($rowErrors)) {
            $response['row_errors'] = $rowErrors;
        }

        return response()->json($response);
    }

    public function exportXml()
    {
        $events = MedicalCalendar::orderBy('date')->orderBy('sort_order')->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<medical_calendar exported_at="' . $this->xmlEscape(now()->toAtomString()) . '">' . "\n";

        foreach ($events as $event) {
            $xml .= "  <event>\n";
            $xml .= '    <id>' . $event->id . "</id>\n";
            $xml .= '    <date>' . $this->xmlEscape(optional($event->date)->format('Y-m-d')) . "</date>\n";
            $xml .= '    <end_date>' . $this->xmlEscape(optional($event->end_date)->format('Y-m-d')) . "</end_date>\n";
            $xml .= '    <title>' . $this->xmlEscape($event->title) . "</title>\n";
            $xml .= '    <description>' . $this->xmlEscape($event->description) . "</description>\n";
            $xml .= '    <type>' . $this->xmlEscape($event->type) . "</type>\n";
            $xml .= '    <category>' . $this->xmlEscape($event->category) . "</category>\n";
            $xml .= '    <color>' . $this->xmlEscape($event->color) . "</color>\n";
            $xml .= '    <is_active>' . ($event->is_active ? '1' : '0') . "</is_active>\n";
            $xml .= '    <sort_order>' . (int) $event->sort_order . "</sort_order>\n";
            $xml .= "  </event>\n";
        }

        $xml .= '</medical_calendar>';

        $filename = 'medical-calendar-' . now()->format('Y-m-d-His') . '.xml';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function getCategories()
    {
        $categories = MedicalCalendar::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->orderBy('category')
            ->pluck('category');

        return response()->json($categories);
    }

    private function parseBoolValue(string $value): bool
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, ['1', 'true', 'yes', 'da'], true);
    }

    private function xmlEscape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
