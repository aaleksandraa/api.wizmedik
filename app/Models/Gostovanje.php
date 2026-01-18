<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gostovanje extends Model
{
    protected $table = 'klinika_doktor_gostovanja';

    protected $fillable = [
        'klinika_id',
        'doktor_id',
        'datum',
        'vrijeme_od',
        'vrijeme_do',
        'slot_trajanje_minuti',
        'pauze',
        'usluge',
        'prihvata_online_rezervacije',
        'status',
        'napomena',
    ];

    protected $casts = [
        'datum' => 'date',
        'pauze' => 'array',
        'usluge' => 'array',
        'slot_trajanje_minuti' => 'integer',
        'prihvata_online_rezervacije' => 'boolean',
    ];

    public function klinika()
    {
        return $this->belongsTo(Klinika::class);
    }

    public function doktor()
    {
        return $this->belongsTo(Doktor::class);
    }

    public function termini()
    {
        return $this->hasMany(Termin::class, 'gostovanje_id');
    }

    public function gostovanjeUsluge()
    {
        return $this->hasMany(GostovanjeUsluga::class, 'gostovanje_id');
    }

    public function aktivneUsluge()
    {
        return $this->hasMany(GostovanjeUsluga::class, 'gostovanje_id')->where('aktivna', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('datum', '>=', now()->toDateString());
    }

    public function scopeForClinic($query, $clinicId)
    {
        return $query->where('klinika_id', $clinicId);
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doktor_id', $doctorId);
    }
}
