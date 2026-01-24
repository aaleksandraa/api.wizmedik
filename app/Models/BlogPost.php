<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $fillable = [
        'naslov', 'slug', 'excerpt', 'sadrzaj', 'thumbnail',
        'autor_id', 'doktor_id', 'status', 'featured', 'views',
        'meta_title', 'meta_description', 'meta_keywords', 'published_at',
        'reading_time_manual'
    ];

    protected $casts = [
        'featured' => 'boolean',
        'views' => 'integer',
        'published_at' => 'datetime',
    ];

    protected $appends = ['autor_name', 'reading_time'];

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function doktor()
    {
        return $this->belongsTo(Doktor::class);
    }

    public function categories()
    {
        return $this->belongsToMany(BlogCategory::class, 'blog_post_category');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function getAutorNameAttribute()
    {
        if ($this->doktor) {
            return 'Dr. ' . $this->doktor->ime . ' ' . $this->doktor->prezime;
        }
        return $this->autor?->name ?? 'WizMedik';
    }

    public function getReadingTimeAttribute()
    {
        // Ako je ručno postavljeno vrijeme čitanja, koristi ga
        if ($this->reading_time_manual) {
            return $this->reading_time_manual;
        }
        // Inače izračunaj na osnovu broja riječi
        $words = str_word_count(strip_tags($this->sadrzaj ?? ''));
        return max(1, ceil($words / 200));
    }

    public function getThumbnailUrlAttribute()
    {
        if (!$this->thumbnail) return null;
        if (filter_var($this->thumbnail, FILTER_VALIDATE_URL)) return $this->thumbnail;
        return url('storage/' . $this->thumbnail);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->naslov);
                $count = static::where('slug', 'like', $post->slug . '%')->count();
                if ($count > 0) $post->slug .= '-' . ($count + 1);
            }
            if (empty($post->excerpt) && $post->sadrzaj) {
                $post->excerpt = Str::limit(strip_tags($post->sadrzaj), 200);
            }
        });
    }
}
