<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalCalendar;
use Illuminate\Http\Request;
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

    public function getCategories()
    {
        $categories = MedicalCalendar::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->orderBy('category')
            ->pluck('category');

        return response()->json($categories);
    }
}
