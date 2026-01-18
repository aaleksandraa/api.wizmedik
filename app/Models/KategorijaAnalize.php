<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class KategorijaAnalize extends Model
{
    use HasFactory;

    protected $table = 'kategorije_analiza';

    protected $fillable = [
        'naziv',
        'slug',
        'opis',
        'ikona',
        'boja',
        'redoslijed',
        'aktivan',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'aktivan' => 'boolean',
        'redoslijed' => 'integer',
    ];

    /**
     * Boot method - auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kategorija) {
            if (empty($kategorija->slug)) {
                $kategorija->slug = Str::slug($kategorija->naziv);

                $count = 1;
                while (static::where('slug', $kategorija->slug)->exists()) {
                    $kategorija->slug = Str::slug($kategorija->naziv) . '-' . $count;
                    $count++;
                }
            }
        });
    }

    /**
     * Relationships
     */
    public function analize(): HasMany
    {
        return $this->hasMany(Analiza::class, 'kategorija_id');
    }

    public function aktivneAnalize(): HasMany
    {
        return $this->hasMany(Analiza::class, 'kategorija_id')->where('aktivan', true);
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
     * Get route key name
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
