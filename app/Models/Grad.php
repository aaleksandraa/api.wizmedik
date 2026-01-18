<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Grad extends Model
{
    protected $table = 'gradovi';

    protected $fillable = [
        'naziv',
        'u_gradu',
        'slug',
        'opis',
        'detaljni_opis',
        'populacija',
        'broj_bolnica',
        'broj_doktora',
        'broj_klinika',
        'hitna_pomoc',
        'kljucne_tacke',
        'aktivan',
    ];

    protected $casts = [
        'kljucne_tacke' => 'array',
        'aktivan' => 'boolean',
        'broj_bolnica' => 'integer',
        'broj_doktora' => 'integer',
        'broj_klinika' => 'integer',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('aktivan', true);
    }

    // Boot method for auto slug generation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($grad) {
            if (empty($grad->slug)) {
                $grad->slug = Str::slug($grad->naziv);
            }
        });

        static::updating(function ($grad) {
            if ($grad->isDirty('naziv')) {
                $grad->slug = Str::slug($grad->naziv);
            }
        });
    }
}
