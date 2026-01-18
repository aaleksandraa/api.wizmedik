<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indikacija extends Model
{
    use HasFactory;

    protected $table = 'indikacije';

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
        return $this->belongsToMany(Banja::class, 'banja_indikacije', 'indikacija_id', 'banja_id')
            ->withPivot('prioritet', 'napomena')
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
}
