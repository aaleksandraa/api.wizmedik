<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoktorGostovanjeUsluga extends Model
{
    use HasFactory;

    protected $table = 'doktor_gostovanje_usluge';

    protected $fillable = [
        'gostovanje_id',
        'naziv',
        'opis',
        'cijena',
        'trajanje_minuti',
        'redoslijed',
        'aktivan',
    ];

    protected $casts = [
        'cijena' => 'decimal:2',
        'trajanje_minuti' => 'integer',
        'redoslijed' => 'integer',
        'aktivan' => 'boolean',
    ];

    /**
     * Gostovanje relationship
     */
    public function gostovanje()
    {
        return $this->belongsTo(DoktorGostovanje::class, 'gostovanje_id');
    }
}
