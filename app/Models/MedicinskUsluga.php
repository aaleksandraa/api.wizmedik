<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicinskUsluga extends Model
{
    use HasFactory;

    protected $table = 'medicinske_usluge';

    protected $fillable = [
        'naziv',
        'slug',
        'opis',
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
    public function domovi()
    {
        return $this->belongsToMany(Dom::class, 'dom_medicinske_usluge', 'usluga_id', 'dom_id')
            ->withPivot('napomena')
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
