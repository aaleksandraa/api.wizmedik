<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Traits\InvalidatesCityCache;

class Laboratorija extends Model
{
    use HasFactory, SoftDeletes, InvalidatesCityCache;

    protected $table = 'laboratorije';

    protected $fillable = [
        'user_id',
        'naziv',
        'slug',
        'opis',
        'kratak_opis',
        'email',
        'telefon',
        'telefon_2',
        'website',
        'adresa',
        'grad',
        'postanski_broj',
        'latitude',
        'longitude',
        'google_maps_link',
        'featured_slika',
        'profilna_slika',
        'galerija',
        'radno_vrijeme',
        'klinika_id',
        'doktor_id',
        'online_rezultati',
        'prosjecno_vrijeme_rezultata',
        'napomena',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'aktivan',
        'verifikovan',
        'verifikovan_at',
        'broj_pregleda',
        'prosjecna_ocjena',
        'broj_recenzija',
    ];

    protected $casts = [
        'galerija' => 'array',
        'radno_vrijeme' => 'array',
        'meta_keywords' => 'array',
        'online_rezultati' => 'boolean',
        'aktivan' => 'boolean',
        'verifikovan' => 'boolean',
        'verifikovan_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'prosjecna_ocjena' => 'decimal:2',
        'broj_pregleda' => 'integer',
        'broj_recenzija' => 'integer',
    ];

    protected $appends = ['full_address', 'rating_display'];

    /**
     * Boot method - auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($laboratorija) {
            if (empty($laboratorija->slug)) {
                $laboratorija->slug = Str::slug($laboratorija->naziv);

                // Ensure unique slug
                $count = 1;
                while (static::where('slug', $laboratorija->slug)->exists()) {
                    $laboratorija->slug = Str::slug($laboratorija->naziv) . '-' . $count;
                    $count++;
                }
            }
        });

        static::updating(function ($laboratorija) {
            if ($laboratorija->isDirty('naziv') && empty($laboratorija->slug)) {
                $laboratorija->slug = Str::slug($laboratorija->naziv);
            }
        });
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function klinika(): BelongsTo
    {
        return $this->belongsTo(Klinika::class);
    }

    public function doktor(): BelongsTo
    {
        return $this->belongsTo(Doktor::class);
    }

    public function analize(): HasMany
    {
        return $this->hasMany(Analiza::class);
    }

    public function aktivneAnalize(): HasMany
    {
        return $this->hasMany(Analiza::class)->where('aktivan', true);
    }

    public function galerija(): HasMany
    {
        return $this->hasMany(LaboratorijaGalerija::class)->orderBy('redoslijed');
    }

    public function radnoVrijeme(): HasMany
    {
        return $this->hasMany(LaboratorijaRadnoVrijeme::class);
    }

    public function recenzije(): HasMany
    {
        return $this->hasMany(LaboratorijaRecenzija::class, 'laboratorija_id');
    }

    public function odobreneRecenzije(): HasMany
    {
        return $this->hasMany(LaboratorijaRecenzija::class, 'laboratorija_id')
            ->where('odobreno', true)
            ->orderBy('created_at', 'desc');
    }

    public function paketi(): HasMany
    {
        return $this->hasMany(PaketAnaliza::class);
    }

    /**
     * Scopes
     */
    public function scopeAktivan($query)
    {
        return $query->where('aktivan', true);
    }

    public function scopeVerifikovan($query)
    {
        return $query->where('verifikovan', true);
    }

    public function scopeByGrad($query, $grad)
    {
        return $query->where('grad', $grad);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('naziv', 'ILIKE', "%{$term}%")
              ->orWhere('opis', 'ILIKE', "%{$term}%")
              ->orWhere('adresa', 'ILIKE', "%{$term}%")
              ->orWhere('grad', 'LIKE', "%{$term}%");
        });
    }

    public function scopeWithOnlineRezultati($query)
    {
        return $query->where('online_rezultati', true);
    }

    /**
     * Accessors
     */
    public function getFullAddressAttribute(): string
    {
        return trim("{$this->adresa}, {$this->grad}");
    }

    public function getRatingDisplayAttribute(): string
    {
        return number_format($this->prosjecna_ocjena, 1);
    }

    /**
     * Helper methods
     */
    public function incrementViews(): void
    {
        $this->increment('broj_pregleda');
    }

    public function updateRating(): void
    {
        $avgRating = $this->recenzije()->avg('ocjena');
        $count = $this->recenzije()->count();

        $this->update([
            'prosjecna_ocjena' => $avgRating ?? 0,
            'broj_recenzija' => $count,
        ]);
    }

    public function isOpen(string $day = null, string $time = null): bool
    {
        $day = $day ?? strtolower(date('l'));
        $time = $time ?? date('H:i');

        $dayMapping = [
            'monday' => 'ponedeljak',
            'tuesday' => 'utorak',
            'wednesday' => 'srijeda',
            'thursday' => 'cetvrtak',
            'friday' => 'petak',
            'saturday' => 'subota',
            'sunday' => 'nedjelja',
        ];

        $bosnianDay = $dayMapping[$day] ?? $day;

        $workingHours = $this->radnoVrijeme()
            ->where('dan', $bosnianDay)
            ->first();

        if (!$workingHours || $workingHours->zatvoreno) {
            return false;
        }

        return $time >= $workingHours->otvaranje && $time <= $workingHours->zatvaranje;
    }

    public function getTodayWorkingHours(): ?LaboratorijaRadnoVrijeme
    {
        $today = strtolower(date('l'));
        $dayMapping = [
            'monday' => 'ponedeljak',
            'tuesday' => 'utorak',
            'wednesday' => 'srijeda',
            'thursday' => 'cetvrtak',
            'friday' => 'petak',
            'saturday' => 'subota',
            'sunday' => 'nedjelja',
        ];

        $bosnianDay = $dayMapping[$today];

        return $this->radnoVrijeme()->where('dan', $bosnianDay)->first();
    }

    /**
     * Get route key name for URL binding
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
