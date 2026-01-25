<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoktorKlinikaZahtjev extends Model
{
    use HasFactory;

    protected $table = 'doktor_klinika_zahtjevi';

    protected $fillable = [
        'doktor_id',
        'klinika_id',
        'poruka',
        'odgovor',
        'initiated_by',
        'status',
    ];

    /**
     * Doktor relationship
     */
    public function doktor()
    {
        return $this->belongsTo(Doktor::class, 'doktor_id');
    }

    /**
     * Klinika relationship
     */
    public function klinika()
    {
        return $this->belongsTo(Klinika::class, 'klinika_id');
    }
}
