<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class RegistrationRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'status',
        'email',
        'password',
        'ime',
        'prezime',
        'naziv',
        'telefon',
        'adresa',
        'grad',
        'regija',
        'specijalnost',
        'specijalnost_id',
        'email_verification_token',
        'email_verified_at',
        'verification_code',
        'documents',
        'message',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        'rejection_reason',
        'ip_address',
        'user_agent',
        'attempts',
        'user_id',
        'doctor_id',
        'clinic_id',
        'laboratory_id',
        'spa_id',
        'care_home_id',
        'expires_at',
    ];

    protected $casts = [
        'documents' => 'array',
        'email_verified_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'expires_at' => 'datetime',
        'attempts' => 'integer',
    ];

    protected $hidden = [
        'password',
        'email_verification_token',
        'verification_code',
    ];

    /**
     * Relationships
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doktor::class, 'doctor_id');
    }

    public function clinic()
    {
        return $this->belongsTo(Klinika::class, 'clinic_id');
    }

    public function laboratory()
    {
        return $this->belongsTo(Laboratorija::class, 'laboratory_id');
    }

    public function spa()
    {
        return $this->belongsTo(Banja::class, 'spa_id');
    }

    public function careHome()
    {
        return $this->belongsTo(Dom::class, 'care_home_id');
    }

    public function specialty()
    {
        return $this->belongsTo(Specijalnost::class, 'specijalnost_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeDoctor($query)
    {
        return $query->where('type', 'doctor');
    }

    public function scopeClinic($query)
    {
        return $query->where('type', 'clinic');
    }

    public function scopeLaboratory($query)
    {
        return $query->where('type', 'laboratory');
    }

    public function scopeSpa($query)
    {
        return $query->where('type', 'spa');
    }

    public function scopeCareHome($query)
    {
        return $query->where('type', 'care_home');
    }

    /**
     * Accessors & Mutators
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsVerifiedAttribute(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function getCanBeApprovedAttribute(): bool
    {
        return $this->status === 'pending'
            && $this->is_verified
            && !$this->is_expired;
    }

    /**
     * Methods
     */
    public function markAsVerified(): void
    {
        $this->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'verification_code' => null,
        ]);
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');

        $maxAttempts = (int) SiteSetting::get('registration_max_attempts', 3);

        if ($this->attempts >= $maxAttempts) {
            $this->update(['status' => 'expired']);
        }
    }

    public function approve(User $admin): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);
    }

    public function reject(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Check if request has expired
     */
    public function checkExpiration(): void
    {
        if ($this->is_expired && $this->status === 'pending') {
            $this->update(['status' => 'expired']);
        }
    }
}
