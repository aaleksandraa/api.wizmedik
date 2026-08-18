<?php

namespace App\Services\HealthcareImport;

use App\Models\Grad;
use App\Models\ImportEntityMapping;
use App\Models\Specijalnost;
use App\Services\HealthcareImport\Matchers\CityMatcher;
use App\Services\HealthcareImport\Matchers\SpecialtyMatcher;

class ImportContext
{
    public string $source;

    public bool $dryRun = true;

    public bool $updateExisting = false;

    public bool $skipMedia = true;

    public int $chunk = 500;

    /** @var list<string> */
    public array $only = [];

    public string $uuid;

    public string $filename;

    public ?string $fileHash = null;

    public ?int $batchId = null;

    public bool $canPersistAudit = false;

    public ImportResult $result;

    public WorkbookReader $workbook;

    public CityMatcher $cities;

    public SpecialtyMatcher $specialties;

    /** @var array<string, array<string, array{model_type:string,model_id:int,payload:?array}>> */
    public array $mappings = [];

    /** @var array<string, int> institution external id => klinika id */
    public array $institutionIds = [];

    /** @var array<string, int> doctor external id => doktor id */
    public array $doctorIds = [];

    /** @var array<int, array<string, mixed>> */
    public array $dryClinics = [];

    /** @var array<int, array<string, mixed>> */
    public array $dryDoctors = [];

    /** @var array<int, true> IDs created or explicitly opened for fill-empty this run */
    public array $writableClinicIds = [];

    /** @var array<int, true> */
    public array $writableDoctorIds = [];

    private int $nextDryId = -1;

    /** @var list<array<string, mixed>> */
    public array $pendingAuditRows = [];

    public function __construct()
    {
        $this->source = (string) config('healthcare-import.source', 'wizmedik_healthcare_master_2026');
        $this->uuid = (string) \Illuminate\Support\Str::uuid();
        $this->result = new ImportResult();
        $this->result->uuid = $this->uuid;
        $this->cities = new CityMatcher();
        $this->specialties = new SpecialtyMatcher();
    }

    public function shouldRun(string $phase): bool
    {
        if ($this->only === []) {
            return true;
        }

        return in_array($phase, $this->only, true);
    }

    public function preloadLookups(): void
    {
        $this->cities->load(Grad::query()->get(['id', 'naziv', 'slug']));
        $this->specialties->load(Specijalnost::query()->get(['id', 'naziv', 'slug', 'parent_id']));

        if (!\Illuminate\Support\Facades\Schema::hasTable('import_entity_mappings')) {
            return;
        }

        $rows = ImportEntityMapping::query()
            ->where('source', $this->source)
            ->get();

        foreach ($rows as $row) {
            $this->mappings[$row->entity_type][$row->external_id] = [
                'model_type' => $row->model_type,
                'model_id' => (int) $row->model_id,
                'payload' => $row->payload,
            ];

            if ($row->entity_type === 'institution' && $row->model_type === \App\Models\Klinika::class) {
                $this->institutionIds[$row->external_id] = (int) $row->model_id;
            }
            if ($row->entity_type === 'doctor' && $row->model_type === \App\Models\Doktor::class) {
                $this->doctorIds[$row->external_id] = (int) $row->model_id;
            }
        }
    }

    public function mappedId(string $entityType, ?string $externalId): ?int
    {
        if ($externalId === null || $externalId === '') {
            return null;
        }

        return $this->mappings[$entityType][$externalId]['model_id'] ?? null;
    }

    public function rememberMapping(string $entityType, string $externalId, string $modelType, int $modelId, ?array $payload = null): void
    {
        $this->mappings[$entityType][$externalId] = [
            'model_type' => $modelType,
            'model_id' => $modelId,
            'payload' => $payload,
        ];

        if ($entityType === 'institution') {
            $this->institutionIds[$externalId] = $modelId;
        }
        if ($entityType === 'doctor') {
            $this->doctorIds[$externalId] = $modelId;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function rememberDryClinic(string $externalId, array $attributes): int
    {
        $id = $this->nextDryId--;
        $this->dryClinics[$id] = $attributes;
        $this->institutionIds[$externalId] = $id;
        $this->rememberMapping('institution', $externalId, \App\Models\Klinika::class, $id, [
            'tip' => $attributes['tip'] ?? null,
            'google_place_id' => $attributes['google_place_id'] ?? null,
        ]);
        $this->markClinicWritable($id);

        return $id;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function rememberDryDoctor(string $externalId, array $attributes): int
    {
        $id = $this->nextDryId--;
        $this->dryDoctors[$id] = $attributes;
        $this->doctorIds[$externalId] = $id;
        $this->rememberMapping('doctor', $externalId, \App\Models\Doktor::class, $id);
        $this->markDoctorWritable($id);

        return $id;
    }

    public function markClinicWritable(int $id): void
    {
        $this->writableClinicIds[$id] = true;
    }

    public function markDoctorWritable(int $id): void
    {
        $this->writableDoctorIds[$id] = true;
    }

    public function isClinicWritable(?int $id): bool
    {
        return $id !== null && isset($this->writableClinicIds[$id]);
    }

    public function isDoctorWritable(?int $id): bool
    {
        return $id !== null && isset($this->writableDoctorIds[$id]);
    }

    public function resolveClinic(?int $id): ?\App\Models\Klinika
    {
        if (!$id) {
            return null;
        }
        if ($id < 0 && isset($this->dryClinics[$id])) {
            $clinic = new \App\Models\Klinika($this->dryClinics[$id]);
            $clinic->id = $id;
            $clinic->exists = false;

            return $clinic;
        }

        return \App\Models\Klinika::query()->find($id);
    }

    public function resolveDoctor(?int $id): ?\App\Models\Doktor
    {
        if (!$id) {
            return null;
        }
        if ($id < 0 && isset($this->dryDoctors[$id])) {
            $doctor = new \App\Models\Doktor($this->dryDoctors[$id]);
            $doctor->id = $id;
            $doctor->exists = false;

            return $doctor;
        }

        return \App\Models\Doktor::query()->find($id);
    }
}
