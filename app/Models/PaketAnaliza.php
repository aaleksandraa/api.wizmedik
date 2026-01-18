<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaketAnaliza extends Model
{
    use HasFactory;

    protected $table = 'paketi_analiza';

    protected $fillable = [
        'laboratorija_id',
        'naziv',
        'slug',
        'opis',
        'cijena',
        'ustedite',
        'prikazi_ustedite',
        'analize_ids',
        'aktivan',
        'redoslijed',
    ];

    protected $casts = [
        'cijena' => 'decimal:2',
        'ustedite' => 'decimal:2',
        'analize_ids' => 'array',
        'aktivan' => 'boolean',
        'prikazi_ustedite' => 'boolean',
        'redoslijed' => 'integer',
    ];

    protected $appends = ['broj_analiza', 'procenat_ustede'];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($paket) {
            if (empty($paket->slug)) {
                $baseSlug = Str::slug($paket->naziv);
                $paket->slug = $baseSlug;

                $count = 1;
                while (static::where('laboratorija_id', $paket->laboratorija_id)
                    ->where('slug', $paket->slug)
                    ->exists()) {
                    $paket->slug = $baseSlug . '-' . $count;
                    $count++;
                }
            }
        });
    }

    /**
     * Relationships
     */
    public function laboratorija(): BelongsTo
    {
        return $this->belongsTo(Laboratorija::class);
    }

    /**
     * Get analyses in this package
     */
    public function getAnalize()
    {
        if (empty($this->analize_ids)) {
            return collect();
        }

        return Analiza::whereIn('id', $this->analize_ids)
            ->where('aktivan', true)
            ->get();
    }

    /**
     * Scopes
     */
    public function scopeAktivan($query)
    {
        return $query->where('aktivan', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('redoslijed')->orderBy('naziv');
    }

    /**
     * Accessors
     */
    public function getBrojAnalizaAttribute(): int
    {
        return count($this->analize_ids ?? []);
    }

    public function getProcenatUstedeAttribute(): ?int
    {
        if (!$this->ustedite) {
            return null;
        }

        $originalPrice = $this->cijena + $this->ustedite;
        if ($originalPrice <= 0) {
            return null;
        }

        return (int) round(($this->ustedite / $originalPrice) * 100);
    }

    /**
     * Calculate total price of individual analyses
     */
    public function calculateOriginalPrice(): float
    {
        $analize = $this->getAnalize();
        return $analize->sum('trenutna_cijena');
    }

    /**
     * Calculate savings
     */
    public function calculateSavings(): float
    {
        $originalPrice = $this->calculateOriginalPrice();
        return max(0, $originalPrice - $this->cijena);
    }

    /**
     * Get route key name
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
