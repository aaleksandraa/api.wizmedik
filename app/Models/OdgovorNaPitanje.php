<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdgovorNaPitanje extends Model
{
    protected $table = 'odgovori_na_pitanja';

    protected $fillable = [
        'pitanje_id',
        'doktor_id',
        'sadrzaj',
        'je_prihvacen',
        'broj_lajkova',
    ];

    protected $casts = [
        'je_prihvacen' => 'boolean',
        'broj_lajkova' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($odgovor) {
            // Označi pitanje kao odgovoreno
            $odgovor->pitanje->oznacKaoOdgovoreno();
        });
    }

    // Relationships
    public function pitanje(): BelongsTo
    {
        return $this->belongsTo(Pitanje::class);
    }

    public function doktor(): BelongsTo
    {
        return $this->belongsTo(Doktor::class);
    }

    // Methods
    public function prihvatiKaoNajbolji(): void
    {
        // Ukloni prethodni prihvaćeni odgovor
        $this->pitanje->odgovori()->update(['je_prihvacen' => false]);

        // Postavi ovaj kao prihvaćen
        $this->update(['je_prihvacen' => true]);
    }

    public function povecajLajkove(): void
    {
        $this->increment('broj_lajkova');
    }
}
