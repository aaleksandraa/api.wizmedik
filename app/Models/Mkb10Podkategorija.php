<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mkb10Podkategorija extends Model
{
    use HasFactory;

    protected $table = 'mkb10_podkategorije';

    protected $fillable = [
        'kategorija_id',
        'kod_od',
        'kod_do',
        'naziv',
        'opis',
        'redoslijed',
        'aktivan',
    ];

    protected $casts = [
        'aktivan' => 'boolean',
        'redoslijed' => 'integer',
    ];

    public function kategorija()
    {
        return $this->belongsTo(Mkb10Kategorija::class, 'kategorija_id');
    }

    public function dijagnoze()
    {
        return $this->hasMany(Mkb10Dijagnoza::class, 'podkategorija_id')->orderBy('kod');
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
}
