<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApotekaPopust extends Model
{
    use HasFactory;

    protected $table = 'apoteke_popusti';

    protected $fillable = [
        'firma_id',
        'poslovnica_id',
        'tip',
        'discount_percent',
        'discount_amount',
        'min_purchase',
        'days_of_week',
        'starts_at',
        'ends_at',
        'uslovi',
        'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function firma(): BelongsTo
    {
        return $this->belongsTo(ApotekaFirma::class, 'firma_id');
    }

    public function poslovnica(): BelongsTo
    {
        return $this->belongsTo(ApotekaPoslovnica::class, 'poslovnica_id');
    }
}

