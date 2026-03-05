<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApotekaRadnoVrijemeIzuzetak extends Model
{
    use HasFactory;

    protected $table = 'apoteke_radno_vrijeme_izuzeci';

    protected $fillable = [
        'poslovnica_id',
        'date',
        'open_time',
        'close_time',
        'closed',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
        'closed' => 'boolean',
    ];

    public function poslovnica(): BelongsTo
    {
        return $this->belongsTo(ApotekaPoslovnica::class, 'poslovnica_id');
    }
}

