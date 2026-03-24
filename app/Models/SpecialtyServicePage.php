<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpecialtyServicePage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'specialty_id',
        'naziv',
        'slug',
        'kratki_opis',
        'sadrzaj',
        'status',
        'is_indexable',
        'show_doctor_cta',
        'sort_order',
        'published_at',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_image',
        'created_by',
    ];

    protected $casts = [
        'is_indexable' => 'boolean',
        'show_doctor_cta' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function specialty()
    {
        return $this->belongsTo(Specijalnost::class, 'specialty_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }
}
