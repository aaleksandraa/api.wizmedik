<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mkb10Dijagnoza extends Model
{
    use HasFactory;

    protected $table = 'mkb10_dijagnoze';

    protected $fillable = [
        'kategorija_id',
        'podkategorija_id',
        'kod',
        'naziv',
        'naziv_lat',
        'opis',
        'ukljucuje',
        'iskljucuje',
        'sinonimi',
        'aktivan',
    ];

    protected $casts = [
        'aktivan' => 'boolean',
        'sinonimi' => 'array',
    ];

    public function kategorija()
    {
        return $this->belongsTo(Mkb10Kategorija::class, 'kategorija_id');
    }

    public function podkategorija()
    {
        return $this->belongsTo(Mkb10Podkategorija::class, 'podkategorija_id');
    }

    public function scopeAktivan($query)
    {
        return $query->where('aktivan', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('kod', 'ILIKE', "%{$term}%")
              ->orWhere('naziv', 'ILIKE', "%{$term}%")
              ->orWhere('naziv_lat', 'ILIKE', "%{$term}%");
        });
    }

    public function scopeByKategorija($query, int $kategorijaId)
    {
        return $query->where('kategorija_id', $kategorijaId);
    }

    public function scopeByPodkategorija($query, int $podkategorijaId)
    {
        return $query->where('podkategorija_id', $podkategorijaId);
    }
}
