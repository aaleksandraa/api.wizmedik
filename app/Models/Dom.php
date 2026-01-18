<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\InvalidatesCityCache;

class Dom extends Model
{
    use HasFactory, SoftDeletes, InvalidatesCityCache;

    protected $table = 'domovi_njega';

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
        'tip_doma_id',
        'nivo_njege_id',
        'accepts_tags',
        'not_accepts_text',
        'nurses_availability',
        'doctor_availability',
        'has_physiotherapist',
        'has_physiatrist',
        'emergency_protocol',
        'emergency_protocol_text',
        'controlled_entry',
        'video_surveillance',
        'visiting_rules',
        'pricing_mode',
        'price_from',
        'price_includes',
        'extra_charges',
        'online_upit',
        'verifikovan',
        'aktivan',
        'prosjecna_ocjena',
        'broj_recenzija',
        'broj_pregleda',
        'featured_slika',
        'galerija',
        'radno_vrijeme',
        'faqs',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accepts_tags' => 'array',
        'has_physiotherapist' => 'boolean',
        'has_physiatrist' => 'boolean',
        'emergency_protocol' => 'boolean',
        'controlled_entry' => 'boolean',
        'video_surveillance' => 'boolean',
        'online_upit' => 'boolean',
        'verifikovan' => 'boolean',
        'aktivan' => 'boolean',
        'prosjecna_ocjena' => 'decimal:2',
        'broj_recenzija' => 'integer',
        'broj_pregleda' => 'integer',
        'price_from' => 'decimal:2',
        'galerija' => 'array',
        'radno_vrijeme' => 'array',
        'faqs' => 'array',
    ];

    protected $appends = ['url', 'formatted_price', 'nurses_availability_label', 'doctor_availability_label'];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug
        static::creating(function ($dom) {
            if (empty($dom->slug)) {
                $dom->slug = Str::slug($dom->naziv);
            }
        });

        // Audit log on create
        static::created(function ($dom) {
            $dom->logAudit('create', null, $dom->toArray());
        });

        // Audit log on update
        static::updating(function ($dom) {
            $original = $dom->getOriginal();
            $changes = $dom->getDirty();

            if (!empty($changes)) {
                $dom->logAudit('update', $original, $changes);
            }
        });

        // Audit log on delete
        static::deleting(function ($dom) {
            $dom->logAudit('delete', $dom->toArray(), null);
        });
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tipDoma()
    {
        return $this->belongsTo(TipDoma::class, 'tip_doma_id');
    }

    public function nivoNjege()
    {
        return $this->belongsTo(NivoNjege::class, 'nivo_njege_id');
    }

    public function programiNjege()
    {
        return $this->belongsToMany(ProgramNjege::class, 'dom_programi_njege', 'dom_id', 'program_id')
            ->withPivot('prioritet', 'napomena')
            ->withTimestamps()
            ->orderBy('dom_programi_njege.prioritet');
    }

    public function medicinskUsluge()
    {
        return $this->belongsToMany(MedicinskUsluga::class, 'dom_medicinske_usluge', 'dom_id', 'usluga_id')
            ->withPivot('napomena')
            ->withTimestamps();
    }

    public function smjestajUslovi()
    {
        return $this->belongsToMany(SmjestajUslov::class, 'dom_smjestaj_uslovi', 'dom_id', 'uslov_id')
            ->withTimestamps();
    }

    public function recenzije()
    {
        return $this->hasMany(DomRecenzija::class, 'dom_id');
    }

    public function odobreneRecenzije()
    {
        return $this->hasMany(DomRecenzija::class, 'dom_id')
            ->where('odobreno', true)
            ->orderBy('created_at', 'desc');
    }

    public function upiti()
    {
        return $this->hasMany(DomUpit::class, 'dom_id');
    }

    public function auditLog()
    {
        return $this->hasMany(DomAuditLog::class, 'dom_id');
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

    public function scopePoTipu($query, $tipId)
    {
        return $query->where('tip_doma_id', $tipId);
    }

    public function scopePoNivou($query, $nivoId)
    {
        return $query->where('nivo_njege_id', $nivoId);
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
        return "/domovi-njega/{$this->slug}";
    }

    public function getFormattedPriceAttribute()
    {
        if ($this->pricing_mode === 'on_request') {
            return 'Na upit';
        }

        if ($this->price_from) {
            return 'Od ' . number_format($this->price_from, 2) . ' KM/mjesečno';
        }

        return 'Na upit';
    }

    public function getFeaturedSlikaAttribute($value)
    {
        if (!$value) {
            return null;
        }

        if (str_starts_with($value, 'http')) {
            return $value;
        }

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

        return array_map(function ($img) {
            if (str_starts_with($img, 'http')) {
                return $img;
            }
            return \Storage::url($img);
        }, $galerija);
    }

    public function getNursesAvailabilityLabelAttribute()
    {
        return match($this->nurses_availability) {
            '24_7' => '24/7',
            'shifts' => 'Smjene',
            'on_demand' => 'Po potrebi',
            default => $this->nurses_availability,
        };
    }

    public function getDoctorAvailabilityLabelAttribute()
    {
        return match($this->doctor_availability) {
            'permanent' => 'Stalno',
            'periodic' => 'Periodično',
            'on_call' => 'Po pozivu',
            default => $this->doctor_availability,
        };
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
        DomAuditLog::create([
            'dom_id' => $this->id,
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
    public function hasProgram($programSlug)
    {
        return $this->programiNjege()->where('slug', $programSlug)->exists();
    }

    public function hasUsluga($uslugaSlug)
    {
        return $this->medicinskUsluge()->where('slug', $uslugaSlug)->exists();
    }

    public function hasUslov($uslovSlug)
    {
        return $this->smjestajUslovi()->where('slug', $uslovSlug)->exists();
    }

    public function getGlavneProgrameNjege()
    {
        return $this->programiNjege()->wherePivot('prioritet', 1)->get();
    }
}
