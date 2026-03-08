<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RfzoLista;
use App\Support\LijekCacheVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RfzoListaController extends Controller
{
    public function index(): JsonResponse
    {
        $lists = RfzoLista::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id', 'code', 'naziv', 'pojasnjenje']);

        return response()->json([
            'success' => true,
            'data' => $lists,
        ]);
    }

    public function adminIndex(): JsonResponse
    {
        $lists = RfzoLista::query()
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $lists,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->all();
        $payload['code'] = strtoupper(trim((string) ($payload['code'] ?? '')));

        $validated = validator($payload, [
            'code' => 'required|string|max:16|unique:rfzo_liste,code',
            'naziv' => 'nullable|string|max:128',
            'pojasnjenje' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ])->validate();

        $list = RfzoLista::query()->create([
            'code' => $validated['code'],
            'naziv' => $validated['naziv'] ?? null,
            'pojasnjenje' => $validated['pojasnjenje'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        LijekCacheVersion::bump();

        return response()->json([
            'success' => true,
            'message' => 'RFZO lista je uspjesno dodana.',
            'data' => $list,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $list = RfzoLista::query()->findOrFail($id);

        $payload = $request->all();
        if (array_key_exists('code', $payload)) {
            $payload['code'] = strtoupper(trim((string) $payload['code']));
        }

        $validated = validator($payload, [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:16',
                Rule::unique('rfzo_liste', 'code')->ignore($list->id),
            ],
            'naziv' => 'nullable|string|max:128',
            'pojasnjenje' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ])->validate();

        $list->fill($validated);
        $list->save();

        LijekCacheVersion::bump();

        return response()->json([
            'success' => true,
            'message' => 'RFZO lista je uspjesno azurirana.',
            'data' => $list,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $list = RfzoLista::query()->findOrFail($id);
        $list->delete();

        LijekCacheVersion::bump();

        return response()->json([
            'success' => true,
            'message' => 'RFZO lista je obrisana.',
        ]);
    }
}
