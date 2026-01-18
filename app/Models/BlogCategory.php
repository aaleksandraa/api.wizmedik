<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    protected $fillable = ['naziv', 'slug', 'opis'];

    public function posts()
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_category');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($cat) {
            if (empty($cat->slug)) {
                $cat->slug = Str::slug($cat->naziv);
            }
        });
    }
}
