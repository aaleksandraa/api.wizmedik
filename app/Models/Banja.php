<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\InvalidatesCityCache;

class Banja extends Model
{
    use HasFactory, SoftDeletes, InvalidatesCityCache;

    protected $table = 'banje';

    protected $fillable = [
        'user_id',
        'naziv',
        'slug',
        'grad',
        'regija',
        'adresa',
        'latitude',
        'longitude',
        'google_maps_link',
        'telefon',
        'email',
        'website',
        'opis',
        'detaljni_opis',
        'medicinski_nadzor',
        'fizijatar_prisutan',
        'medicinsko_osoblje',
        'ima_smjestaj',
        'broj_kreveta',
        'online_rezervacija',
        'online_upit',
        'verifikovan',
        'aktivan',
        'prosjecna_ocjena',
        'broj_recenzija',
        'broj_pregleda',
        'featured_slika',
        'galerija',
        'radno_vrijeme',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'medicinski_nadzor' => 'boolean',
        'fizijatar_prisutan' => 'boolean',
        'ima_smjestaj' => 'boolean',
        'online_rezervacija' => 'boolean',
        'online_upit' => 'boolean',
        'verifikovan' => 'boolean',
        'aktivan' => 'boolean',
        'prosjecna_ocjena' => 'decimal:2',
        'broj_recenzija' => 'integer',
        'broj_pregleda' => 'integer',
        'broj_kreveta' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'galerija' => 'array',
        'radno_vrijeme' => 'array',
    ];

    protected $appends = ['url', 'customTerapije'];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug
        static::creating(function ($banja) {
            if (empty($banja->slug)) {
                $banja->slug = Str::slug($banja->naziv);
            }
        });

        // Audit log on create
        static::created(function ($banja) {
            $banja->logAudit('create', null, $banja->toArray());
        });

        // Audit log on update
        static::updating(function ($banja) {
            $original = $banja->getOriginal();
            $changes = $banja->getDirty();

            if (!empty($changes)) {
                $banja->logAudit('update', $original, $changes);
            }
        });

        // Audit log on delete
        static::deleting(function ($banja) {
            $banja->logAudit('delete', $banja->toArray(), null);
        });
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vrste()
    {
        return $this->belongsToMany(VrstaBanje::class, 'banja_vrste', 'banja_id', 'vrsta_id')
            ->withTimestamps();
    }

    public function indikacije()
    {
        return $this->belongsToMany(Indikacija::class, 'banja_indikacije', 'banja_id', 'indikacija_id')
            ->withPivot('prioritet', 'napomena')
            ->withTimestamps()
            ->orderBy('prioritet');
    }

    public function terapije()
    {
        return $this->belongsToMany(Terapija::class, 'banja_terapije', 'banja_id', 'terapija_id')
            ->withPivot('cijena', 'trajanje_minuta', 'napomena')
            ->withTimestamps();
    }

    public function customTerapije()
    {
        return $this->hasMany(BanjaCustomTerapija::class, 'banja_id')
            ->ordered();
    }

    // Alias for camelCase compatibility
    public function getCustomTerapijeAttribute()
    {
        if (!$this->relationLoaded('customTerapije')) {
            $this->load('customTerapije');
        }
        return $this->getRelation('customTerapije');
    }

    public function paketi()
    {
        return $this->hasMany(BanjaPaket::class, 'banja_id')
            ->orderBy('redoslijed');
    }

    public function recenzije()
    {
        return $this->hasMany(BanjaRecenzija::class, 'banja_id');
    }

    public function odobreneRecenzije()
    {
        return $this->hasMany(BanjaRecenzija::class, 'banja_id')
            ->where('odobreno', true)
            ->orderBy('created_at', 'desc');
    }

    public function upiti()
    {
        return $this->hasMany(BanjaUpit::class, 'banja_id');
    }

    public function auditLog()
    {
        return $this->hasMany(BanjaAuditLog::class, 'banja_id');
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

    public function scopePoGradu($query, $grad)
    {
        return $query->where('grad', $grad);
    }

    public function scopePoRegiji($query, $regija)
    {
        return $query->where('regija', $regija);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('naziv', 'ILIKE', "%{$term}%")
              ->orWhere('opis', 'ILIKE', "%{$term}%")
              ->orWhere('grad', 'ILIKE', "%{$term}%");
        });
    }

    /**
     * Accessors
     */
    public function getUrlAttribute()
    {
        return "/banje/{$this->slug}";
    }

    public function getFeaturedSlikaAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // If already a full URL, return as is
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        // Fix corrupted paths with multiple /storage/ prefixes
        $value = preg_replace('#(/storage)+#', '/storage', $value);

        // If it starts with /storage/, add the app URL
        if (str_starts_with($value, '/storage/')) {
            return url($value);
        }

        // Otherwise add full storage URL
        return \Storage::url($value);
    }

    public function getGalerijaAttribute($value)
    {
        if (!$value) {
            return [];
        }

        $galerija = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($galerija)) {
            return [];
        }

        // Normalize and add proper URL prefix to images
        return array_map(function ($img) {
            // If already a full URL (http/https), return as is
            if (str_starts_with($img, 'http')) {
                return $img;
            }

            // Fix corrupted paths with multiple /storage/ prefixes
            $img = preg_replace('#(/storage)+#', '/storage', $img);

            // If it starts with /storage/, add the app URL
            if (str_starts_with($img, '/storage/')) {
                return url($img);
            }

            // Otherwise add full storage URL
            return \Storage::url($img);
        }, $galerija);
    }

    /**
     * Methods
     */
    public function incrementViews()
    {
        $this->increment('broj_pregleda');
    }

    public function updateRating()
    {
        $stats = $this->odobreneRecenzije()
            ->selectRaw('AVG(ocjena) as avg_rating, COUNT(*) as count')
            ->first();

        $this->update([
            'prosjecna_ocjena' => $stats->avg_rating ?? 0,
            'broj_recenzija' => $stats->count ?? 0,
        ]);
    }

    public function logAudit($akcija, $stareVrijednosti = null, $noveVrijednosti = null)
    {
        BanjaAuditLog::create([
            'banja_id' => $this->id,
            'user_id' => auth()->id(),
            'akcija' => $akcija,
            'stare_vrijednosti' => $stareVrijednosti,
            'nove_vrijednosti' => $noveVrijednosti,
            'ip_adresa' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Helper methods
     */
    public function hasVrsta($vrstaSlug)
    {
        return $this->vrste()->where('slug', $vrstaSlug)->exists();
    }

    public function hasIndikacija($indikacijaSlug)
    {
        return $this->indikacije()->where('slug', $indikacijaSlug)->exists();
    }

    public function hasTerapija($terapijaSlug)
    {
        return $this->terapije()->where('slug', $terapijaSlug)->exists();
    }

    public function getGlavneIndikacije()
    {
        return $this->indikacije()->wherePivot('prioritet', 1)->get();
    }
}
