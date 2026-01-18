<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Specijalnost extends Model
{
    protected $table = 'specijalnosti';

    protected $fillable = [
        'naziv',
        'slug',
        'icon_url',
        'parent_id',
        'opis',
        'aktivan',
        'sort_order',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'kljucne_rijeci',
        'detaljan_opis',
        'prikazi_video_savjete',
        'youtube_linkovi',
        'prikazi_faq',
        'faq',
        'prikazi_usluge',
        'usluge',
        'uvodni_tekst',
        'zakljucni_tekst',
        'canonical_url',
        'og_image',
    ];

    protected $casts = [
        'aktivan' => 'boolean',
        'prikazi_video_savjete' => 'boolean',
        'prikazi_faq' => 'boolean',
        'prikazi_usluge' => 'boolean',
        'youtube_linkovi' => 'json',
        'faq' => 'json',
        'usluge' => 'json',
        'kljucne_rijeci' => 'json',
    ];

    /**
     * Ensure youtube_linkovi is always returned as an array
     */
    public function getYoutubeLinkoviAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        // Ensure it's always an array
        if (!is_array($decoded)) {
            return [];
        }

        // If it's an associative array (single object), wrap it in an array
        if (isset($decoded['url'])) {
            return [$decoded];
        }

        return $decoded;
    }

    /**
     * Ensure faq is always returned as an array
     */
    public function getFaqAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($decoded)) {
            return [];
        }

        // If it's an associative array (single object), wrap it in an array
        if (isset($decoded['pitanje'])) {
            return [$decoded];
        }

        return $decoded;
    }

    /**
     * Ensure usluge is always returned as an array
     */
    public function getUslugeAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($decoded)) {
            return [];
        }

        // If it's an associative array (single object), wrap it in an array
        if (isset($decoded['naziv']) && !isset($decoded[0])) {
            return [$decoded];
        }

        return $decoded;
    }

    // Relationships
    public function parent()
    {
        return $this->belongsTo(Specijalnost::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Specijalnost::class, 'parent_id');
    }

    public function doktori()
    {
        return $this->belongsToMany(Doktor::class, 'doktor_specijalnost');
    }

    public function klinike()
    {
        return $this->belongsToMany(Klinika::class, 'klinika_specijalnost');
    }

    // Scopes
    public function scopeActive($query)
    {
        // Check if aktivan column exists, otherwise return all
        if (\Schema::hasColumn('specijalnosti', 'aktivan')) {
            return $query->where('aktivan', true);
        }
        return $query;
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    // Boot method for auto slug generation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($specijalnost) {
            if (empty($specijalnost->slug)) {
                $specijalnost->slug = Str::slug($specijalnost->naziv);
            }
        });

        static::updating(function ($specijalnost) {
            if ($specijalnost->isDirty('naziv')) {
                $specijalnost->slug = Str::slug($specijalnost->naziv);
            }
        });
    }
}
