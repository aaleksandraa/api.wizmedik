<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmjestajUslov extends Model
{
    use HasFactory;

    protected $table = 'smjestaj_uslovi';

    protected $fillable = [
        'naziv',
        'slug',
        'kategorija',
        'opis',
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
    public function domovi()
    {
        return $this->belongsToMany(Dom::class, 'dom_smjestaj_uslovi', 'uslov_id', 'dom_id')
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
