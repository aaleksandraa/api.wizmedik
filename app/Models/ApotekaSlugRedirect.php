<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApotekaSlugRedirect extends Model
{
    use HasFactory;

    protected $table = 'apoteka_slug_redirects';

    protected $fillable = [
        'poslovnica_id',
        'old_slug',
    ];

    public function poslovnica(): BelongsTo
    {
        return $this->belongsTo(ApotekaPoslovnica::class, 'poslovnica_id');
    }
}
