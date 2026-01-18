<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Pitanje extends Model
{
    protected $table = 'pitanja';

    protected $fillable = [
        'user_id',
        'naslov',
        'sadrzaj',
        'ime_korisnika',
        'email_korisnika',
        'specijalnost_id',
        'slug',
        'tagovi',
        'broj_pregleda',
        'je_odgovoreno',
        'je_javno',
        'ip_adresa',
    ];

    protected $casts = [
        'tagovi' => 'array',
        'je_odgovoreno' => 'boolean',
        'je_javno' => 'boolean',
        'broj_pregleda' => 'integer',
    ];

    protected $appends = ['url'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pitanje) {
            if (empty($pitanje->slug)) {
                $pitanje->slug = Str::slug($pitanje->naslov) . '-' . Str::random(6);
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specijalnost(): BelongsTo
    {
        return $this->belongsTo(Specijalnost::class);
    }

    public function odgovori(): HasMany
    {
        return $this->hasMany(OdgovorNaPitanje::class);
    }

    public function notifikacije(): HasMany
    {
        return $this->hasMany(NotifikacijaPitanja::class);
    }

    // Accessors
    public function getUrlAttribute(): string
    {
        return "/pitanja/{$this->slug}";
    }

    // Scopes
    public function scopeJavna($query)
    {
        return $query->where('je_javno', true);
    }

    public function scopeOdgovorena($query)
    {
        return $query->where('je_odgovoreno', true);
    }

    public function scopeNeodgovorena($query)
    {
        return $query->where('je_odgovoreno', false);
    }

    public function scopePoSpecijalnosti($query, $specijalnostId)
    {
        return $query->where('specijalnost_id', $specijalnostId);
    }

    public function scopePretraga($query, $termin)
    {
        return $query->where(function ($q) use ($termin) {
            $q->where('naslov', 'ilike', "%{$termin}%")
              ->orWhere('sadrzaj', 'ilike', "%{$termin}%")
              ->orWhereJsonContains('tagovi', $termin);
        });
    }

    public function scopePoTagovima($query, array $tagovi)
    {
        return $query->where(function ($q) use ($tagovi) {
            foreach ($tagovi as $tag) {
                $q->orWhereJsonContains('tagovi', $tag);
            }
        });
    }

    // Methods
    public function povecajPreglede(): void
    {
        $this->increment('broj_pregleda');
    }

    public function oznacKaoOdgovoreno(): void
    {
        $this->update(['je_odgovoreno' => true]);
    }
}
