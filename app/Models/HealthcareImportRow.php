<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthcareImportRow extends Model
{
    protected $table = 'healthcare_import_rows';

    protected $fillable = [
        'batch_id',
        'sheet_name',
        'row_number',
        'entity_type',
        'external_id',
        'action',
        'status',
        'matched_model_type',
        'matched_model_id',
        'source_hash',
        'raw_payload',
        'normalized_payload',
        'warnings',
        'errors',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'normalized_payload' => 'array',
        'warnings' => 'array',
        'errors' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(HealthcareImportBatch::class, 'batch_id');
    }
}
