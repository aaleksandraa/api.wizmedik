<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratorijaRecenzija extends Model
{
    use HasFactory;

    protected $table = 'laboratorija_recenzije';

    protected $fillable = [
        'laboratorija_id',
        'user_id',
        'ime',
        'ocjena',
        'komentar',
        'verifikovano',
        'odobreno',
        'ip_adresa',
    ];

    protected $casts = [
        'ocjena' => 'integer',
        'verifikovano' => 'boolean',
        'odobreno' => 'boolean',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Update laboratorija rating when review is approved/disapproved
        static::saved(function ($recenzija) {
            if ($recenzija->wasChanged('odobreno')) {
                $recenzija->laboratorija->updateRating();
            }
        });

        // Update laboratorija rating when review is deleted
        static::deleted(function ($recenzija) {
            $recenzija->laboratorija->updateRating();
        });
    }

    /**
     * Relationships
     */
    public function laboratorija()
    {
        return $this->belongsTo(Laboratorija::class, 'laboratorija_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeOdobreno($query)
    {
        return $query->where('odobreno', true);
    }

    public function scopeVerifikovano($query)
    {
        return $query->where('verifikovano', true);
    }

    public function scopePoOcjeni($query, $ocjena)
    {
        return $query->where('ocjena', $ocjena);
    }

    /**
     * Accessors
     */
    public function getAutorAttribute()
    {
        return $this->user ? $this->user->name : $this->ime;
    }

    public function getStarsAttribute()
    {
        return str_repeat('★', $this->ocjena) . str_repeat('☆', 5 - $this->ocjena);
    }

    /**
     * Helper methods
     */
    public function approve()
    {
        $this->update(['odobreno' => true]);
    }

    public function disapprove()
    {
        $this->update(['odobreno' => false]);
    }

    public function verify()
    {
        $this->update(['verifikovano' => true]);
    }
}
