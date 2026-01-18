<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BanjaPaket extends Model
{
    use HasFactory;

    protected $table = 'banja_paketi';

    protected $fillable = [
        'banja_id',
        'naziv',
        'opis',
        'trajanje_dana',
        'cijena',
        'ukljuceno',
        'aktivan',
        'redoslijed',
    ];

    protected $casts = [
        'trajanje_dana' => 'integer',
        'cijena' => 'decimal:2',
        'ukljuceno' => 'array',
        'aktivan' => 'boolean',
        'redoslijed' => 'integer',
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

    public function getFormattedTrajanje()
    {
        if (!$this->trajanje_dana) {
            return null;
        }

        if ($this->trajanje_dana === 1) {
            return '1 dan';
        }

        return $this->trajanje_dana . ' dana';
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
