<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthcareImportBatch extends Model
{
    protected $table = 'healthcare_import_batches';

    protected $fillable = [
        'uuid',
        'source_filename',
        'source_hash',
        'status',
        'dry_run',
        'started_at',
        'completed_at',
        'total_rows',
        'created_rows',
        'updated_rows',
        'skipped_rows',
        'review_rows',
        'failed_rows',
        'report_path',
        'metadata',
    ];

    protected $casts = [
        'dry_run' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(HealthcareImportRow::class, 'batch_id');
    }
}
