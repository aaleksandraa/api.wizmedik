<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomUpit extends Model
{
    use HasFactory;

    protected $table = 'dom_upiti';

    protected $fillable = [
        'dom_id',
        'user_id',
        'ime',
        'email',
        'telefon',
        'poruka',
        'opis_potreba',
        'zelja_posjeta',
        'tip',
        'status',
        'ip_adresa',
    ];

    protected $casts = [
        'zelja_posjeta' => 'boolean',
    ];

    const TIPOVI = [
        'upit' => 'Upit',
        'rezervacija' => 'Rezervacija',
    ];

    const STATUSI = [
        'novi' => 'Novi',
        'procitan' => 'Pročitan',
        'odgovoren' => 'Odgovoren',
        'zatvoren' => 'Zatvoren',
    ];

    /**
     * Relationships
     */
    public function dom()
    {
        return $this->belongsTo(Dom::class, 'dom_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopePoStatusu($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePoTipu($query, $tip)
    {
        return $query->where('tip', $tip);
    }

    public function scopeNovi($query)
    {
        return $query->where('status', 'novi');
    }

    public function scopeNeobradjeni($query)
    {
        return $query->whereIn('status', ['novi', 'procitan']);
    }

    /**
     * Accessors
     */
    public function getTipLabelAttribute()
    {
        return self::TIPOVI[$this->tip] ?? $this->tip;
    }

    public function getStatusLabelAttribute()
    {
        return self::STATUSI[$this->status] ?? $this->status;
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'novi' => 'bg-red-100 text-red-800',
            'procitan' => 'bg-yellow-100 text-yellow-800',
            'odgovoren' => 'bg-blue-100 text-blue-800',
            'zatvoren' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Helper methods
     */
    public function markAsRead()
    {
        if ($this->status === 'novi') {
            $this->update(['status' => 'procitan']);
        }
    }

    public function markAsAnswered()
    {
        $this->update(['status' => 'odgovoren']);
    }

    public function close()
    {
        $this->update(['status' => 'zatvoren']);
    }

    public function isNew()
    {
        return $this->status === 'novi';
    }

    public function isProcessed()
    {
        return in_array($this->status, ['odgovoren', 'zatvoren']);
    }
}
