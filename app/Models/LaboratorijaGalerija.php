<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LaboratorijaGalerija extends Model
{
    use HasFactory;

    protected $table = 'laboratorija_galerija';

    protected $fillable = [
        'laboratorija_id',
        'slika_url',
        'thumbnail_url',
        'naslov',
        'opis',
        'redoslijed',
    ];

    protected $casts = [
        'redoslijed' => 'integer',
    ];

    /**
     * Relationships
     */
    public function laboratorija(): BelongsTo
    {
        return $this->belongsTo(Laboratorija::class);
    }

    /**
     * Scopes
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('redoslijed');
    }

    /**
     * Helper methods
     */
    public function getFullImageUrl(): string
    {
        if (filter_var($this->slika_url, FILTER_VALIDATE_URL)) {
            return $this->slika_url;
        }
        return Storage::url($this->slika_url);
    }

    public function getFullThumbnailUrl(): ?string
    {
        if (!$this->thumbnail_url) {
            return null;
        }

        if (filter_var($this->thumbnail_url, FILTER_VALIDATE_URL)) {
            return $this->thumbnail_url;
        }
        return Storage::url($this->thumbnail_url);
    }

    /**
     * Delete image files when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($galerija) {
            if ($galerija->slika_url && !filter_var($galerija->slika_url, FILTER_VALIDATE_URL)) {
                Storage::delete($galerija->slika_url);
            }
            if ($galerija->thumbnail_url && !filter_var($galerija->thumbnail_url, FILTER_VALIDATE_URL)) {
                Storage::delete($galerija->thumbnail_url);
            }
        });
    }
}
