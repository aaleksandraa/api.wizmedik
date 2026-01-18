<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VrstaBanje extends Model
{
    use HasFactory;

    protected $table = 'vrste_banja';

    protected $fillable = [
        'naziv',
        'slug',
        'opis',
        'ikona',
        'redoslijed',
        'aktivan',
    ];

    protected $casts = [
        'aktivan' => 'boolean',
        'redoslijed' => 'integer',
    ];

    /**
     * Relationships
     */
    public function banje()
    {
        return $this->belongsToMany(Banja::class, 'banja_vrste', 'vrsta_id', 'banja_id')
            ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeAktivan($query)
    {
        return $query->where('aktivan', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('redoslijed');
    }
}
