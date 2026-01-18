<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramNjege extends Model
{
    use HasFactory;

    protected $table = 'programi_njege';

    protected $fillable = [
        'naziv',
        'slug',
        'opis',
        'redoslijed',
        'aktivan',
    ];

    protected $casts = [
        'aktivan' => 'boolean',
        'redoslijed' => 'integer',
    ];

    /**
     * Relationships
     */
    public function domovi()
    {
        return $this->belongsToMany(Dom::class, 'dom_programi_njege', 'program_id', 'dom_id')
            ->withPivot('prioritet', 'napomena')
            ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeAktivan($query)
    {
        return $query->where('aktivan', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('redoslijed');
    }
}
