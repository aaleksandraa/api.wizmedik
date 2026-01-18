<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaboratorijaRadnoVrijeme extends Model
{
    use HasFactory;

    protected $table = 'laboratorija_radno_vrijeme';

    protected $fillable = [
        'laboratorija_id',
        'dan',
        'otvaranje',
        'zatvaranje',
        'pauza_od',
        'pauza_do',
        'zatvoreno',
        'napomena',
    ];

    protected $casts = [
        'zatvoreno' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function laboratorija(): BelongsTo
    {
        return $this->belongsTo(Laboratorija::class);
    }

    /**
     * Scopes
     */
    public function scopeByDan($query, $dan)
    {
        return $query->where('dan', $dan);
    }

    public function scopeOtvoreno($query)
    {
        return $query->where('zatvoreno', false);
    }

    /**
     * Helper methods
     */
    public function isOpen(string $time = null): bool
    {
        if ($this->zatvoreno) {
            return false;
        }

        $time = $time ?? date('H:i');

        $isOpen = $time >= $this->otvaranje && $time <= $this->zatvaranje;

        // Check if in break time
        if ($isOpen && $this->pauza_od && $this->pauza_do) {
            if ($time >= $this->pauza_od && $time <= $this->pauza_do) {
                return false;
            }
        }

        return $isOpen;
    }

    public function getFormattedHours(): string
    {
        if ($this->zatvoreno) {
            return 'Zatvoreno';
        }

        $hours = substr($this->otvaranje, 0, 5) . ' - ' . substr($this->zatvaranje, 0, 5);

        if ($this->pauza_od && $this->pauza_do) {
            $hours .= ' (Pauza: ' . substr($this->pauza_od, 0, 5) . ' - ' . substr($this->pauza_do, 0, 5) . ')';
        }

        return $hours;
    }

    /**
     * Get day name in Bosnian
     */
    public function getDayNameAttribute(): string
    {
        $days = [
            'ponedeljak' => 'Ponedeljak',
            'utorak' => 'Utorak',
            'srijeda' => 'Srijeda',
            'cetvrtak' => 'Četvrtak',
            'petak' => 'Petak',
            'subota' => 'Subota',
            'nedjelja' => 'Nedjelja',
        ];

        return $days[$this->dan] ?? $this->dan;
    }
}
