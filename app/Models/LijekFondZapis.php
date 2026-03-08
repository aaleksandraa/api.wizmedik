<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LijekFondZapis extends Model
{
    use HasFactory;

    protected $table = 'lijek_fond_zapisi';

    protected $fillable = [
        'lijek_id',
        'cijena',
        'procenat_participacije',
        'iznos_participacije',
        'lista_id',
        'indikacija_oznaka',
        'indikacija_naziv',
        'cijena_ref_lijeka',
        'verzija_od',
        'verzija_do',
    ];

    protected $casts = [
        'cijena' => 'decimal:2',
        'procenat_participacije' => 'decimal:2',
        'iznos_participacije' => 'decimal:2',
        'cijena_ref_lijeka' => 'decimal:2',
        'verzija_od' => 'date',
        'verzija_do' => 'date',
    ];

    public function lijek(): BelongsTo
    {
        return $this->belongsTo(Lijek::class, 'lijek_id');
    }
}
