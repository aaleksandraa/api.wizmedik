<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportEntityMapping extends Model
{
    protected $table = 'import_entity_mappings';

    protected $fillable = [
        'source',
        'entity_type',
        'external_id',
        'model_type',
        'model_id',
        'source_hash',
        'import_batch_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
