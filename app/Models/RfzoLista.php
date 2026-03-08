<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfzoLista extends Model
{
    use HasFactory;

    protected $table = 'rfzo_liste';

    protected $fillable = [
        'code',
        'naziv',
        'pojasnjenje',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
