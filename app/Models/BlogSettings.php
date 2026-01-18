<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogSettings extends Model
{
    protected $table = 'blog_settings';

    protected $fillable = [
        'doctors_can_write',
        'homepage_display',
        'homepage_count',
        'featured_post_ids'
    ];

    protected $casts = [
        'doctors_can_write' => 'boolean',
        'homepage_count' => 'integer',
        'featured_post_ids' => 'array',
    ];

    public static function get()
    {
        return static::first() ?? static::create([
            'doctors_can_write' => false,
            'homepage_display' => 'latest',
            'homepage_count' => 3,
        ]);
    }
}
