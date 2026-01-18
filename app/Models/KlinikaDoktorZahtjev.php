<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KlinikaDoktorZahtjev extends Model
{
    protected $table = 'klinika_doktor_zahtjevi';

    protected $fillable = [
        'klinika_id',
        'doktor_id',
        'initiated_by',
        'status',
        'poruka',
        'odgovor',
        'odgovoreno_at',
    ];

    protected $casts = [
        'odgovoreno_at' => 'datetime',
    ];

    public function klinika()
    {
        return $this->belongsTo(Klinika::class);
    }

    public function doktor()
    {
        return $this->belongsTo(Doktor::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
