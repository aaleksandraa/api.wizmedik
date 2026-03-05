<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApotekaAkcija extends Model
{
    use HasFactory;

    protected $table = 'apoteke_akcije';

    protected $fillable = [
        'firma_id',
        'poslovnica_id',
        'naslov',
        'opis',
        'image_url',
        'promo_code',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
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

