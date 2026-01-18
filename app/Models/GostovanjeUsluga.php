<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GostovanjeUsluga extends Model
{
    protected $table = 'gostovanje_usluge';

    protected $fillable = [
        'gostovanje_id',
        'naziv',
        'opis',
        'cijena',
        'trajanje_minuti',
        'dodao',
        'aktivna',
    ];

    protected $casts = [
        'cijena' => 'decimal:2',
        'trajanje_minuti' => 'integer',
        'aktivna' => 'boolean',
    ];

    public function gostovanje()
    {
        return $this->belongsTo(Gostovanje::class);
    }

    public function scopeAktivne($query)
    {
        return $query->where('aktivna', true);
    }

    public function scopeByKlinika($query)
    {
        return $query->where('dodao', 'klinika');
    }

    public function scopeByDoktor($query)
    {
        return $query->where('dodao', 'doktor');
    }
}
