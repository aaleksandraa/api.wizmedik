<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApotekaDezurstvo extends Model
{
    use HasFactory;

    protected $table = 'apoteke_dezurstva';

    protected $fillable = [
        'poslovnica_id',
        'grad_id',
        'starts_at',
        'ends_at',
        'tip',
        'is_nonstop',
        'source',
        'status',
        'note',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_nonstop' => 'boolean',
    ];

    public function poslovnica(): BelongsTo
    {
        return $this->belongsTo(ApotekaPoslovnica::class, 'poslovnica_id');
    }

    public function grad(): BelongsTo
    {
        return $this->belongsTo(Grad::class, 'grad_id');
    }

    public function scopeActiveAt($query, $moment)
    {
        return $query
            ->where('status', 'confirmed')
            ->where('starts_at', '<=', $moment)
            ->where('ends_at', '>', $moment);
    }
}

