<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usluga extends Model
{
    protected $table = 'usluge';

    protected $fillable = [
        'doktor_id',
        'kategorija_id',
        'naziv',
        'opis',
        'cijena',
        'cijena_popust',
        'trajanje_minuti',
        'redoslijed',
        'aktivan',
    ];

    protected $casts = [
        'cijena' => 'decimal:2',
        'cijena_popust' => 'decimal:2',
        'trajanje_minuti' => 'integer',
        'redoslijed' => 'integer',
        'aktivan' => 'boolean',
    ];

    // Relationships
    public function doktor()
    {
        return $this->belongsTo(Doktor::class);
    }

    public function kategorija()
    {
        return $this->belongsTo(DoktorKategorijaUsluga::class, 'kategorija_id');
    }

    public function termini()
    {
        return $this->hasMany(Termin::class, 'usluga_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('aktivan', true);
    }

    public function scopeByDoctor($query, $doktorId)
    {
        return $query->where('doktor_id', $doktorId);
    }
}
