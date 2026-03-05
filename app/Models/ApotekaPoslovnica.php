<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApotekaPoslovnica extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'apoteke_poslovnice';

    protected $fillable = [
        'firma_id',
        'naziv',
        'slug',
        'grad_id',
        'grad_naziv',
        'adresa',
        'postanski_broj',
        'latitude',
        'longitude',
        'telefon',
        'email',
        'kratki_opis',
        'profilna_slika_url',
        'galerija_slike',
        'google_maps_link',
        'ima_dostavu',
        'ima_parking',
        'pristup_invalidima',
        'is_24h',
        'is_active',
        'is_verified',
        'verified_at',
        'verified_by',
        'ocjena',
        'broj_ocjena',
    ];

    protected $casts = [
        'galerija_slike' => 'array',
        'ima_dostavu' => 'boolean',
        'ima_parking' => 'boolean',
        'pristup_invalidima' => 'boolean',
        'is_24h' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'ocjena' => 'decimal:2',
        'broj_ocjena' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $poslovnica) {
            if (empty($poslovnica->slug)) {
                $poslovnica->slug = self::generateUniqueSlug($poslovnica->naziv);
            }
        });
    }

    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : 'apoteka';
        $counter = 2;

        while (
            static::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = ($baseSlug !== '' ? $baseSlug : 'apoteka') . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(ApotekaFirma::class, 'firma_id');
    }

    public function grad(): BelongsTo
    {
        return $this->belongsTo(Grad::class, 'grad_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function radnoVrijeme(): HasMany
    {
        return $this->hasMany(ApotekaRadnoVrijeme::class, 'poslovnica_id');
    }

    public function radnoVrijemeIzuzeci(): HasMany
    {
        return $this->hasMany(ApotekaRadnoVrijemeIzuzetak::class, 'poslovnica_id');
    }

    public function dezurstva(): HasMany
    {
        return $this->hasMany(ApotekaDezurstvo::class, 'poslovnica_id');
    }

    public function popusti(): HasMany
    {
        return $this->hasMany(ApotekaPopust::class, 'poslovnica_id');
    }

    public function akcije(): HasMany
    {
        return $this->hasMany(ApotekaAkcija::class, 'poslovnica_id');
    }

    public function posebnePonude(): HasMany
    {
        return $this->hasMany(ApotekaPosebnaPonuda::class, 'poslovnica_id');
    }

    public function scopePublicVisible($query)
    {
        return $query
            ->where('is_active', true)
            ->where('is_verified', true)
            ->whereHas('firma', function ($firmaQuery) {
                $firmaQuery->where('is_active', true)->where('status', 'verified');
            });
    }
}

