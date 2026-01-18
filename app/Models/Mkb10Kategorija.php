<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mkb10Kategorija extends Model
{
    use HasFactory;

    protected $table = 'mkb10_kategorije';

    protected $fillable = [
        'kod_od',
        'kod_do',
        'naziv',
        'opis',
        'boja',
        'ikona',
        'redoslijed',
        'aktivan',
    ];

    protected $casts = [
        'aktivan' => 'boolean',
        'redoslijed' => 'integer',
    ];

    public function podkategorije()
    {
        return $this->hasMany(Mkb10Podkategorija::class, 'kategorija_id')->orderBy('redoslijed');
    }

    public function dijagnoze()
    {
        return $this->hasMany(Mkb10Dijagnoza::class, 'kategorija_id')->orderBy('kod');
    }

    public function scopeAktivan($query)
    {
        return $query->where('aktivan', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('redoslijed')->orderBy('kod_od');
    }

    public function getKodRangeAttribute(): string
    {
        return "{$this->kod_od}-{$this->kod_do}";
    }

    public function getBrojDijagnozaAttribute(): int
    {
        return $this->dijagnoze()->count();
    }
}
