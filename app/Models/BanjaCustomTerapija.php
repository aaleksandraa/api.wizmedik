<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BanjaCustomTerapija extends Model
{
    use HasFactory;

    protected $table = 'banja_custom_terapije';

    protected $fillable = [
        'banja_id',
        'naziv',
        'opis',
        'cijena',
        'trajanje_minuta',
        'redoslijed',
        'aktivan',
    ];

    protected $casts = [
        'cijena' => 'decimal:2',
        'trajanje_minuta' => 'integer',
        'redoslijed' => 'integer',
        'aktivan' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function banja()
    {
        return $this->belongsTo(Banja::class, 'banja_id');
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

    /**
     * Accessors
     */
    public function getFormattedCijenaAttribute()
    {
        if (!$this->cijena) {
            return 'Na upit';
        }
        return number_format($this->cijena, 2) . ' KM';
    }

    public function getFormattedTrajanjeAttribute()
    {
        if (!$this->trajanje_minuta) {
            return null;
        }
        return $this->trajanje_minuta . ' min';
    }

    /**
     * Helper methods
     */
    public function activate()
    {
        $this->update(['aktivan' => true]);
    }

    public function deactivate()
    {
        $this->update(['aktivan' => false]);
    }

    public function isActive()
    {
        return $this->aktivan;
    }
}
