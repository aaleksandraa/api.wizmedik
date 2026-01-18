<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoktorKategorijaUsluga extends Model
{
    use HasFactory;

    protected $table = 'doktor_kategorije_usluga';

    protected $fillable = [
        'doktor_id',
        'naziv',
        'opis',
        'redoslijed',
        'aktivan',
    ];

    protected $casts = [
        'redoslijed' => 'integer',
        'aktivan' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function doktor()
    {
        return $this->belongsTo(Doktor::class);
    }

    public function usluge()
    {
        return $this->hasMany(Usluga::class, 'kategorija_id')
            ->orderBy('redoslijed')
            ->orderBy('naziv');
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
        return $query->orderBy('redoslijed')->orderBy('naziv');
    }
}
