<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\InvalidatesCityCache;

class Klinika extends Model
{
    use SoftDeletes, InvalidatesCityCache;

    protected $table = 'klinike';

    protected $fillable = [
        'user_id',
        'naziv',
        'slug',
        'opis',
        'adresa',
        'postanski_broj',
        'mjesto',
        'opstina',
        'grad',
        'latitude',
        'longitude',
        'google_maps_link',
        'telefon',
        'email',
        'contact_email',
        'website',
        'slike',
        'radno_vrijeme',
        'pauze',
        'odmori',
        'aktivan',
        'ocjena',
        'broj_ocjena',
        'verifikovan',
        'verifikovan_at',
        'verifikovan_by',
    ];

    protected $casts = [
        'slike' => 'array',
        'radno_vrijeme' => 'array',
        'pauze' => 'array',
        'odmori' => 'array',
        'aktivan' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'ocjena' => 'decimal:1',
        'verifikovan' => 'boolean',
        'verifikovan_at' => 'datetime',
        'broj_ocjena' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function doktori()
    {
        return $this->hasMany(Doktor::class, 'klinika_id');
    }

    public function specijalnosti()
    {
        return $this->belongsToMany(Specijalnost::class, 'klinika_specijalnost');
    }

    public function recenzije()
    {
        return $this->morphMany(Recenzija::class, 'recenziran');
    }

    public function gostovanja()
    {
        return $this->hasMany(Gostovanje::class, 'klinika_id');
    }

    public function gostujuciDoktori()
    {
        return $this->belongsToMany(Doktor::class, 'klinika_doktor_gostovanja')
            ->withPivot(['datum', 'vrijeme_od', 'vrijeme_do', 'status', 'slot_trajanje_minuti'])
            ->wherePivot('status', 'confirmed')
            ->wherePivot('datum', '>=', now()->toDateString());
    }

    public function verifikovaoAdmin()
    {
        return $this->belongsTo(User::class, 'verifikovan_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    // Slike accessor
    public function getSlikeAttribute($value)
    {
        if (!$value) {
            return [];
        }

        $images = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($images)) {
            return [];
        }

        return array_map(function($image) {
            if (filter_var($image, FILTER_VALIDATE_URL)) {
                return $image;
            }
            return url('storage/' . $image);
        }, $images);
    }

    /**
     * Rating display accessor
     */
    public function getRatingDisplayAttribute(): string
    {
        if ($this->broj_ocjena === 0) {
            return 'Nema ocjena';
        }

        return number_format($this->ocjena, 1) . ' (' . $this->broj_ocjena . ')';
    }

    /**
     * Rating percentage accessor
     */
    public function getRatingPercentageAttribute(): float
    {
        return ($this->ocjena / 5) * 100;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('aktivan', true);
    }

    public function scopeByCity($query, $grad)
    {
        return $query->where('grad', $grad);
    }

    /**
     * Scope for verified clinics
     */
    public function scopeVerifikovan($query)
    {
        return $query->where('verifikovan', true);
    }

    /**
     * Scope for visible clinics (active AND verified)
     */
    public function scopeVidljiv($query)
    {
        return $query->where('aktivan', true)
                    ->where('verifikovan', true);
    }

    /**
     * Highly rated clinics
     */
    public function scopeHighlyRated($query, $minRating = 4.0)
    {
        return $query->where('ocjena', '>=', $minRating)
                     ->where('broj_ocjena', '>', 0);
    }

    /**
     * Order clinics by rating
     */
    public function scopeOrderByRating($query, $direction = 'desc')
    {
        return $query->orderBy('ocjena', $direction)
                     ->orderBy('broj_ocjena', $direction);
    }

    /*
    |--------------------------------------------------------------------------
    | Boot - auto slug
    |--------------------------------------------------------------------------
    */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($klinika) {
            if (empty($klinika->slug)) {
                $klinika->slug = Str::slug($klinika->naziv);

                $count = static::whereRaw("slug RLIKE '^{$klinika->slug}(-[0-9]+)?$'")
                    ->count();

                if ($count > 0) {
                    $klinika->slug = "{$klinika->slug}-{$count}";
                }
            }
        });

        static::updating(function ($klinika) {
            if ($klinika->isDirty('naziv')) {
                $baseSlug = Str::slug($klinika->naziv);

                $count = static::whereRaw("slug RLIKE '^{$baseSlug}(-[0-9]+)?$'")
                    ->where('id', '!=', $klinika->id)
                    ->count();

                $klinika->slug = $count > 0 ? "{$baseSlug}-{$count}" : $baseSlug;
            }
        });
    }
}
