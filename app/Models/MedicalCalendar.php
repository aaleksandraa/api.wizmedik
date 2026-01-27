<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalCalendar extends Model
{
    use HasFactory;

    protected $table = 'medical_calendar';

    protected $fillable = [
        'date',
        'title',
        'description',
        'type',
        'end_date',
        'category',
        'color',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)
                     ->whereMonth('date', $month);
    }

    public function scopeByYear($query, $year)
    {
        return $query->whereYear('date', $year);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
