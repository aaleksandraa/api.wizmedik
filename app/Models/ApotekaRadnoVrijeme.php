<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApotekaRadnoVrijeme extends Model
{
    use HasFactory;

    protected $table = 'apoteke_radno_vrijeme';

    protected $fillable = [
        'poslovnica_id',
        'day_of_week',
        'open_time',
        'close_time',
        'closed',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'closed' => 'boolean',
    ];

    public function poslovnica(): BelongsTo
    {
        return $this->belongsTo(ApotekaPoslovnica::class, 'poslovnica_id');
    }
}

