<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\InvalidatesCityCache;

class Doktor extends Model
{
    use SoftDeletes, InvalidatesCityCache;

    protected $table = 'doktori';

    protected $fillable = [
        'user_id',
        'specijalnost_id',
        'klinika_id',
        'ime',
        'prezime',
        'specijalnost',
        'grad',
        'lokacija',
        'postanski_broj',
        'mjesto',
        'opstina',
        'latitude',
        'longitude',
        'google_maps_link',
        'telefon',
        'email',
        'opis',
        'youtube_linkovi',
        'ocjena',
        'broj_ocjena',
        'slika_profila',
        'slug',
        'prihvata_online',
        'auto_potvrda',
        'slot_trajanje_minuti',
        'radno_vrijeme',
        'pauze',
        'odmori',
        'aktivan',
        'verifikovan',
        'verifikovan_at',
        'verifikovan_by',
        'telemedicine_enabled',
        'telemedicine_phone',
        'calendar_sync_token',
        'calendar_sync_enabled',
        'google_calendar_url',
        'outlook_calendar_url',
        'calendar_last_synced',
    ];

    protected $casts = [
        'radno_vrijeme' => 'array',
        'pauze' => 'array',
        'odmori' => 'array',
        'youtube_linkovi' => 'array',
        'prihvata_online' => 'boolean',
        'auto_potvrda' => 'boolean',
        'slot_trajanje_minuti' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'ocjena' => 'decimal:1',
        'broj_ocjena' => 'integer',
        'aktivan' => 'boolean',
        'verifikovan' => 'boolean',
        'verifikovan_at' => 'datetime',
        'calendar_sync_enabled' => 'boolean',
        'calendar_last_synced' => 'datetime',
    ];

    protected $appends = ['slika_url', 'klinika_naziv', 'klinika_adresa'];


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function specijalnostModel()
    {
        return $this->belongsTo(Specijalnost::class, 'specijalnost_id');
    }

    public function specijalnosti()
    {
        return $this->belongsToMany(Specijalnost::class, 'doktor_specijalnost');
    }

    public function klinika()
    {
        return $this->belongsTo(Klinika::class);
    }

    public function usluge()
    {
        return $this->hasMany(Usluga::class, 'doktor_id')
            ->orderBy('redoslijed')
            ->orderBy('naziv');
    }

    public function kategorijeUsluga()
    {
        return $this->hasMany(DoktorKategorijaUsluga::class, 'doktor_id')
            ->ordered();
    }

    public function termini()
    {
        return $this->hasMany(Termin::class, 'doktor_id');
    }

    public function ocjene()
    {
        return $this->hasMany(Ocjena::class, 'doktor_id');
    }

    public function recenzije()
    {
        return $this->morphMany(Recenzija::class, 'recenziran');
    }

    public function gostovanja()
    {
        return $this->hasMany(Gostovanje::class, 'doktor_id');
    }

    public function gostujuceKlinike()
    {
        return $this->belongsToMany(Klinika::class, 'klinika_doktor_gostovanja')
            ->withPivot(['datum', 'vrijeme_od', 'vrijeme_do', 'status', 'slot_trajanje_minuti'])
            ->wherePivot('status', 'confirmed')
            ->wherePivot('datum', '>=', now()->toDateString());
    }

    public function verifikovaoAdmin()
    {
        return $this->belongsTo(User::class, 'verifikovan_by');
    }

    public function blogPosts()
    {
        return $this->hasMany(BlogPost::class, 'doktor_id');
    }


    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeByCity($query, $grad)
    {
        return $query->where('grad', $grad);
    }

    public function scopeBySpecialty($query, $specijalnost)
    {
        return $query->where('specijalnost', $specijalnost);
    }

    public function scopeAcceptingOnline($query)
    {
        return $query->where('prihvata_online', true);
    }

    public function scopeTopRated($query, $minRating = 4.0)
    {
        return $query->where('ocjena', '>=', $minRating)
                     ->where('broj_ocjena', '>', 0);
    }

    /**
     * Scope for active doctors
     */
    public function scopeAktivan($query)
    {
        return $query->where('aktivan', true);
    }

    /**
     * Scope for verified doctors
     */
    public function scopeVerifikovan($query)
    {
        return $query->where('verifikovan', true);
    }

    /**
     * Scope for visible doctors (active AND verified)
     */
    public function scopeVidljiv($query)
    {
        return $query->where('aktivan', true)
                    ->where('verifikovan', true);
    }

    /**
     * Search doctors by name, specialty, or location
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('ime', 'ilike', "%{$search}%")
              ->orWhere('prezime', 'ilike', "%{$search}%")
              ->orWhere('specijalnost', 'ilike', "%{$search}%")
              ->orWhere('grad', 'ilike', "%{$search}%")
              ->orWhereRaw("CONCAT(ime, ' ', prezime) ILIKE ?", ["%{$search}%"]);
        });
    }

    /**
     * Order doctors by rating
     */
    public function scopeOrderByRating($query, $direction = 'desc')
    {
        return $query->orderBy('ocjena', $direction)
                     ->orderBy('broj_ocjena', $direction);
    }


    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    // Full name
    public function getFullNameAttribute()
    {
        return "Dr. {$this->ime} {$this->prezime}";
    }

    // Profile image
    public function getSlikaUrlAttribute()
    {
        if (!$this->slika_profila) {
            return null;
        }

        if (filter_var($this->slika_profila, FILTER_VALIDATE_URL)) {
            return $this->slika_profila;
        }

        return url('storage/' . $this->slika_profila);
    }

    // Clinic name
    public function getKlinikaNazivAttribute()
    {
        return $this->klinika?->naziv;
    }

    // Clinic address
    public function getKlinikaAdresaAttribute()
    {
        return $this->klinika?->adresa;
    }

    /**
     * Rating display
     */
    public function getRatingDisplayAttribute(): string
    {
        if ($this->broj_ocjena === 0) {
            return 'Nema ocjena';
        }

        return number_format($this->ocjena, 1) . ' (' . $this->broj_ocjena . ')';
    }

    /**
     * Rating percentage (za zvjezdice)
     */
    public function getRatingPercentageAttribute(): float
    {
        return ($this->ocjena / 5) * 100;
    }


    /*
    |--------------------------------------------------------------------------
    | Boot (auto slug)
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($doktor) {
            if (empty($doktor->slug)) {
                $baseSlug = Str::slug("{$doktor->ime}-{$doktor->prezime}");
                $doktor->slug = $baseSlug;

                // PostgreSQL compatible regex (using ~ operator)
                $count = static::whereRaw("slug ~ ?", ["^{$baseSlug}(-[0-9]+)?$"])
                    ->count();

                if ($count > 0) {
                    $doktor->slug = "{$baseSlug}-{$count}";
                }
            }
        });

        static::updating(function ($doktor) {
            if ($doktor->isDirty(['ime', 'prezime'])) {
                $baseSlug = Str::slug("{$doktor->ime}-{$doktor->prezime}");

                // PostgreSQL compatible regex (using ~ operator)
                $count = static::whereRaw("slug ~ ?", ["^{$baseSlug}(-[0-9]+)?$"])
                    ->where('id', '!=', $doktor->id)
                    ->count();

                $doktor->slug = $count > 0 ? "{$baseSlug}-{$count}" : $baseSlug;
            }
        });
    }
}
