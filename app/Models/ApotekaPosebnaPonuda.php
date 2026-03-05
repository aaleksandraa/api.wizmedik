<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApotekaPosebnaPonuda extends Model
{
    use HasFactory;

    protected $table = 'apoteke_posebne_ponude';

    protected $fillable = [
        'firma_id',
        'poslovnica_id',
        'offer_type',
        'title',
        'description',
        'target_group',
        'discount_percent',
        'discount_amount',
        'service_name',
        'product_scope',
        'conditions_json',
        'days_of_week',
        'time_from',
        'time_to',
        'starts_at',
        'ends_at',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'product_scope' => 'array',
        'conditions_json' => 'array',
        'days_of_week' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'priority' => 'integer',
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

