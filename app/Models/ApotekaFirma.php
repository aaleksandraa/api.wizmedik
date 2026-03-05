<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApotekaFirma extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'apoteke_firme';

    protected $fillable = [
        'owner_user_id',
        'naziv_brenda',
        'pravni_naziv',
        'jib',
        'broj_licence',
        'telefon',
        'email',
        'website',
        'opis',
        'logo_url',
        'status',
        'is_active',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function poslovnice(): HasMany
    {
        return $this->hasMany(ApotekaPoslovnica::class, 'firma_id');
    }

    public function popusti(): HasMany
    {
        return $this->hasMany(ApotekaPopust::class, 'firma_id');
    }

    public function akcije(): HasMany
    {
        return $this->hasMany(ApotekaAkcija::class, 'firma_id');
    }

    public function posebnePonude(): HasMany
    {
        return $this->hasMany(ApotekaPosebnaPonuda::class, 'firma_id');
    }

    public function scopeVisible($query)
    {
        return $query->where('is_active', true)->where('status', 'verified');
    }
}

