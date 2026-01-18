<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Terapija extends Model
{
    use HasFactory;

    protected $table = 'terapije';

    protected $fillable = [
        'naziv',
        'slug',
        'kategorija',
        'opis',
        'medicinski_opis',
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
        return $this->belongsToMany(Banja::class, 'banja_terapije', 'terapija_id', 'banja_id')
            ->withPivot('cijena', 'trajanje_minuta', 'napomena')
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

    public function scopePoKategoriji($query, $kategorija)
    {
        return $query->where('kategorija', $kategorija);
    }

    /**
     * Helper methods
     */
    public function getFormattedPrice($banjaId = null)
    {
        if ($banjaId) {
            $pivot = $this->banje()->where('banja_id', $banjaId)->first()?->pivot;
            if ($pivot && $pivot->cijena) {
                return number_format($pivot->cijena, 2) . ' KM';
            }
        }
        return 'Na upit';
    }

    public function getFormattedDuration($banjaId = null)
    {
        if ($banjaId) {
            $pivot = $this->banje()->where('banja_id', $banjaId)->first()?->pivot;
            if ($pivot && $pivot->trajanje_minuta) {
                return $pivot->trajanje_minuta . ' min';
            }
        }
        return null;
    }
}
