<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LijekRegistarZapis extends Model
{
    use HasFactory;

    protected $table = 'lijek_registar_zapisi';

    protected $fillable = [
        'lijek_id',
        'source_lijek_id',
        'atc_sifra',
        'inn',
        'jidl',
        'naziv_lijeka',
        'proizvodjac',
        'nosilac_dozvole',
        'oblik',
        'jacina',
        'pakovanje',
        'broj_dozvole',
        'tip_lijeka',
        'podtip_lijeka',
        'vazi_od',
        'vazi_do',
        'datum_rjesenja',
        'rezim_izdavanja',
        'posebne_oznake',
        'nalaz_prve_serije',
        'nalaz_prve_serije_prethodno_rjesenje',
        'import_batch_id',
        'import_row_number',
        'match_status',
        'match_note',
        'raw_payload',
    ];

    protected $casts = [
        'vazi_od' => 'date',
        'vazi_do' => 'date',
        'datum_rjesenja' => 'date',
        'raw_payload' => 'array',
        'source_lijek_id' => 'integer',
        'import_row_number' => 'integer',
    ];

    public function lijek(): BelongsTo
    {
        return $this->belongsTo(Lijek::class, 'lijek_id');
    }
}
