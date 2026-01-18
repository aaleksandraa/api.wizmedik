<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Termin extends Model
{
    use SoftDeletes;

    protected $table = 'termini';

    protected $fillable = [
        'user_id',
        'doktor_id',
        'usluga_id',
        'gostovanje_id',
        'klinika_id',
        'datum_vrijeme',
        'razlog',
        'napomene',
        'status',
        'trajanje_minuti',
        'cijena',
        'guest_ime',
        'guest_prezime',
        'guest_telefon',
        'guest_email',
    ];

    /**
     * Encrypt sensitive data at rest + type casting
     */
    protected $casts = [
        'razlog' => 'encrypted',
        'napomene' => 'encrypted',
        'guest_telefon' => 'encrypted',
        'guest_email' => 'encrypted',
        'datum_vrijeme' => 'datetime',
        'cijena' => 'decimal:2',
        'trajanje_minuti' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function doktor()
    {
        return $this->belongsTo(Doktor::class);
    }

    public function usluga()
    {
        return $this->belongsTo(Usluga::class);
    }

    public function gostovanje()
    {
        return $this->belongsTo(Gostovanje::class);
    }

    public function klinika()
    {
        return $this->belongsTo(Klinika::class);
    }

    public function isGuestVisitBooking()
    {
        return !is_null($this->gostovanje_id);
    }

    // Scopes
    public function scopeByDoctor($query, $doktorId)
    {
        return $query->where('doktor_id', $doktorId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('datum_vrijeme', '>', now())
                     ->whereIn('status', ['zakazan', 'potvrden'])
                     ->orderBy('datum_vrijeme', 'asc');
    }

    public function scopePast($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'zavrshen')
              ->orWhere(function($sq) {
                  $sq->where('datum_vrijeme', '<', now())
                     ->whereNotIn('status', ['otkazan']);
              });
        })->orderBy('datum_vrijeme', 'desc');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'otkazan')
                     ->orderBy('datum_vrijeme', 'desc');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('datum_vrijeme', $date);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('datum_vrijeme', [$startDate, $endDate]);
    }

    // Helper methods
    public function isGuestBooking()
    {
        return is_null($this->user_id);
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['zakazan', 'potvrden'])
               && $this->datum_vrijeme > now();
    }

    public function canBeRescheduled()
    {
        return in_array($this->status, ['zakazan', 'potvrden'])
               && $this->datum_vrijeme > now();
    }

    // Get patient name (for both registered users and guests)
    public function getPatientNameAttribute()
    {
        if ($this->isGuestBooking()) {
            return trim("{$this->guest_ime} {$this->guest_prezime}");
        }

        return $this->user ? trim("{$this->user->ime} {$this->user->prezime}") : 'N/A';
    }

    public function recenzija()
        {
            return $this->hasOne(Recenzija::class);
        }
}
