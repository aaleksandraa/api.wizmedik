<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikacija extends Model
{
    use HasFactory;

    protected $table = 'notifikacije';

    protected $fillable = [
        'user_id',
        'tip',
        'naslov',
        'poruka',
        'data',
        'procitano',
        'procitano_at',
    ];

    protected $casts = [
        'data' => 'array',
        'procitano' => 'boolean',
        'procitano_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeNeprocitane($query)
    {
        return $query->where('procitano', false);
    }

    public function markAsRead()
    {
        $this->update([
            'procitano' => true,
            'procitano_at' => now(),
        ]);
    }
}
