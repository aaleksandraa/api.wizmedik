<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoktorGostovanje extends Model
{
    use HasFactory;

    protected $table = 'doktor_gostovanja';

    protected $fillable = [
        'doktor_id',
        'klinika_id',
        'datum_od',
        'datum_do',
        'status',
        'napomena',
        'initiated_by',
    ];

    protected $casts = [
        'datum_od' => 'datetime',
        'datum_do' => 'datetime',
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

    /**
     * Services relationship
     */
    public function usluge()
    {
        return $this->hasMany(DoktorGostovanjeUsluga::class, 'gostovanje_id');
    }
}
