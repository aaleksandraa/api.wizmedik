<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikacijaPitanja extends Model
{
    protected $table = 'notifikacije_pitanja';

    protected $fillable = [
        'pitanje_id',
        'doktor_id',
        'je_procitano',
        'procitano_u',
    ];

    protected $casts = [
        'je_procitano' => 'boolean',
        'procitano_u' => 'datetime',
    ];

    // Relationships
    public function pitanje(): BelongsTo
    {
        return $this->belongsTo(Pitanje::class);
    }

    public function doktor(): BelongsTo
    {
        return $this->belongsTo(Doktor::class);
    }

    // Scopes
    public function scopeNeprocitane($query)
    {
        return $query->where('je_procitano', false);
    }

    public function scopeZaDoktora($query, $doktorId)
    {
        return $query->where('doktor_id', $doktorId);
    }

    // Methods
    public function oznacKaoProcitano(): void
    {
        $this->update([
            'je_procitano' => true,
            'procitano_u' => now(),
        ]);
    }
}
