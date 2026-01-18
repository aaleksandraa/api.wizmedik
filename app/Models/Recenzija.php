<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recenzija extends Model
{
    protected $table = 'recenzije';

    protected $fillable = [
        'user_id',
        'termin_id',
        'recenziran_type',
        'recenziran_id',
        'ocjena',
        'komentar',
        'odgovor',
        'odgovor_datum',
        'email_poslat',
    ];

    protected $casts = [
        'ocjena' => 'integer',
        'odgovor_datum' => 'datetime',
        'email_poslat' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function termin()
    {
        return $this->belongsTo(Termin::class);
    }

    public function recenziran()
    {
        return $this->morphTo();
    }
}
