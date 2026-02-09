<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        Log::info('User sendEmailVerificationNotification called', [
            'user_id' => $this->id,
            'email' => $this->email
        ]);

        $this->notify(new \Illuminate\Auth\Notifications\VerifyEmail());

        Log::info('Email verification notification sent successfully', [
            'user_id' => $this->id,
            'email' => $this->email
        ]);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        Log::info('User sendPasswordResetNotification called', [
            'user_id' => $this->id,
            'email' => $this->email,
            'token_length' => strlen($token)
        ]);

        $this->notify(new ResetPasswordNotification($token));

        Log::info('User notify() called successfully', [
            'user_id' => $this->id,
            'email' => $this->email
        ]);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'ime',
        'prezime',
        'telefon',
        'datum_rodjenja',
        'adresa',
        'grad',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'datum_rodjenja' => 'date',
        ];
    }

    // Relationships
    public function doktor()
    {
        return $this->hasOne(Doktor::class);
    }

    public function klinika()
    {
        return $this->hasOne(Klinika::class);
    }

    public function laboratorija()
    {
        return $this->hasOne(Laboratorija::class);
    }

    public function banja()
    {
        return $this->hasOne(Banja::class);
    }

    public function termini()
    {
        return $this->hasMany(Termin::class);
    }

    public function recenzije()
    {
        return $this->hasMany(Recenzija::class);
    }

    public function ocjene()
    {
        return $this->hasMany(Ocjena::class);
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isDoctor()
    {
        return $this->hasRole('doctor');
    }

    public function isClinic()
    {
        return $this->hasRole('clinic');
    }

    public function isLaboratory()
    {
        return $this->hasRole('laboratory');
    }

    public function isSpaManager()
    {
        return $this->hasRole('spa_manager');
    }

    public function isPatient()
    {
        return $this->hasRole('patient');
    }

    public function getFullNameAttribute()
    {
        return trim("{$this->ime} {$this->prezime}") ?: $this->name;
    }

    /**
     * Boot method - Add password policy validation
     */
    protected static function boot()
    {
        parent::boot();

        // Password policy enforcement
        static::creating(function ($user) {
            if ($user->password) {
                self::validatePassword($user->password);
            }
        });

        static::updating(function ($user) {
            if ($user->isDirty('password')) {
                self::validatePassword($user->password);
            }
        });
    }

    /**
     * Validate password strength
     */
    protected static function validatePassword($password)
    {
        // Check if password is already hashed (starts with $2y$)
        if (str_starts_with($password, '$2y$')) {
            return; // Already hashed, skip validation
        }

        $errors = [];

        // Minimum length: 12 characters
        if (strlen($password) < 12) {
            $errors[] = 'Password mora imati najmanje 12 karaktera.';
        }

        // Must contain at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password mora sadržati najmanje jedno veliko slovo.';
        }

        // Must contain at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password mora sadržati najmanje jedno malo slovo.';
        }

        // Must contain at least one number
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password mora sadržati najmanje jedan broj.';
        }

        // Must contain at least one special character
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password mora sadržati najmanje jedan specijalni karakter (!@#$%^&*).';
        }

        // Check against common passwords
        $commonPasswords = [
            'password123', 'Password123!', '123456789012', 'qwerty123456',
            'admin123456', 'welcome12345', 'letmein12345'
        ];

        if (in_array($password, $commonPasswords)) {
            $errors[] = 'Password je previše čest. Molimo izaberite jači password.';
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }
    }
}
