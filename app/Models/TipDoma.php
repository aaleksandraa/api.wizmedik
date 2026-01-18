<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipDoma extends Model
{
    use HasFactory;

    protected $table = 'tipovi_domova';

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
        return $this->hasMany(Dom::class, 'tip_doma_id');
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
