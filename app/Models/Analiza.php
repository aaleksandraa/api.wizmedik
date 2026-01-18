<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Analiza extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'analize';

    protected $fillable = [
        'laboratorija_id',
        'kategorija_id',
        'naziv',
        'slug',
        'kod',
        'opis',
        'kratak_opis',
        'cijena',
        'akcijska_cijena',
        'akcija_od',
        'akcija_do',
        'prosjecno_vrijeme_rezultata',
        'priprema',
        'napomena',
        'kljucne_rijeci',
        'sinonimi',
        'hitno_dostupno',
        'kucna_posjeta',
        'online_rezultati',
        'aktivan',
        'redoslijed',
        'broj_pretraga',
        'broj_pregleda',
    ];

    protected $casts = [
        'cijena' => 'decimal:2',
        'akcijska_cijena' => 'decimal:2',
        'akcija_od' => 'date',
        'akcija_do' => 'date',
        'kljucne_rijeci' => 'array',
        'sinonimi' => 'array',
        'hitno_dostupno' => 'boolean',
        'kucna_posjeta' => 'boolean',
        'online_rezultati' => 'boolean',
        'aktivan' => 'boolean',
        'redoslijed' => 'integer',
        'broj_pretraga' => 'integer',
        'broj_pregleda' => 'integer',
    ];

    protected $appends = ['trenutna_cijena', 'ima_akciju', 'ustedite'];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($analiza) {
            if (empty($analiza->slug)) {
                $baseSlug = Str::slug($analiza->naziv);
                $analiza->slug = $baseSlug;

                $count = 1;
                while (static::where('laboratorija_id', $analiza->laboratorija_id)
                    ->where('slug', $analiza->slug)
                    ->exists()) {
                    $analiza->slug = $baseSlug . '-' . $count;
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

    public function kategorija(): BelongsTo
    {
        return $this->belongsTo(KategorijaAnalize::class, 'kategorija_id');
    }

    /**
     * Scopes
     */
    public function scopeAktivan($query)
    {
        return $query->where('aktivan', true);
    }

    public function scopeByKategorija($query, $kategorijaId)
    {
        return $query->where('kategorija_id', $kategorijaId);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('naziv', 'ILIKE', "%{$term}%")
              ->orWhere('opis', 'ILIKE', "%{$term}%")
              ->orWhere('kratak_opis', 'ILIKE', "%{$term}%")
              ->orWhere('kod', 'LIKE', "%{$term}%")
              ->orWhereJsonContains('kljucne_rijeci', $term)
              ->orWhereJsonContains('sinonimi', $term);
        });
    }

    public function scopeNaAkciji($query)
    {
        $today = Carbon::today();
        return $query->whereNotNull('akcijska_cijena')
            ->where(function ($q) use ($today) {
                $q->whereNull('akcija_od')
                  ->orWhere('akcija_od', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('akcija_do')
                  ->orWhere('akcija_do', '>=', $today);
            });
    }

    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('cijena', [$min, $max]);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('redoslijed')->orderBy('naziv');
    }

    /**
     * Accessors
     */
    public function getTrenutnaCijenaAttribute(): float
    {
        if ($this->ima_akciju) {
            return (float) $this->akcijska_cijena;
        }
        return (float) $this->cijena;
    }

    public function getImaAkcijuAttribute(): bool
    {
        if (!$this->akcijska_cijena) {
            return false;
        }

        $today = Carbon::today();

        if ($this->akcija_od && $today->lt($this->akcija_od)) {
            return false;
        }

        if ($this->akcija_do && $today->gt($this->akcija_do)) {
            return false;
        }

        return true;
    }

    public function getUstediteAttribute(): ?float
    {
        if ($this->ima_akciju) {
            return (float) ($this->cijena - $this->akcijska_cijena);
        }
        return null;
    }

    /**
     * Helper methods
     */
    public function incrementViews(): void
    {
        $this->increment('broj_pregleda');
    }

    public function incrementSearches(): void
    {
        $this->increment('broj_pretraga');
    }

    public function isOnSale(): bool
    {
        return $this->ima_akciju;
    }

    public function getSalePercentage(): ?int
    {
        if (!$this->ima_akciju) {
            return null;
        }

        $discount = (($this->cijena - $this->akcijska_cijena) / $this->cijena) * 100;
        return (int) round($discount);
    }

    /**
     * Get route key name
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
