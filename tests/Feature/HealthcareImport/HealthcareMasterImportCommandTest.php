<?php

namespace Tests\Feature\HealthcareImport;

use App\Models\Doktor;
use App\Models\Grad;
use App\Models\Klinika;
use App\Models\Specijalnost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class HealthcareMasterImportCommandTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as runRefreshDatabase;
    }

    /**
     * RefreshDatabase runs before setUp(). Skip first so we never wipe wizmedik.
     */
    protected function refreshDatabase(): void
    {
        if ($this->isLiveWizmedikDatabase()) {
            $this->markTestSkipped('Ovi testovi koriste RefreshDatabase i ne smiju se pokretati na bazi wizmedik.');
        }

        $this->runRefreshDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isLiveWizmedikDatabase()) {
            $this->markTestSkipped('Ovi testovi koriste RefreshDatabase i ne smiju se pokretati na bazi wizmedik.');
        }
    }

    private function isLiveWizmedikDatabase(): bool
    {
        $database = (string) config('database.connections.' . config('database.default') . '.database');

        return $database === 'wizmedik' && env('ALLOW_WIZMEDIK_TEST_DB') !== 'true';
    }

    public function test_dry_run_does_not_write_clinics_or_doctors(): void
    {
        $this->seedLookups();
        $path = $this->writeFixture();

        $this->artisan('wizmedik:import-healthcare-master', [
            'file' => $path,
            '--dry-run' => true,
            '--report' => storage_path('framework/testing-healthcare-import'),
        ])->assertSuccessful();

        $this->assertSame(0, Klinika::query()->count());
        $this->assertSame(0, Doktor::query()->count());
        $this->assertNotEmpty(glob(storage_path('framework/testing-healthcare-import/healthcare-import-*.json')));
    }

    public function test_import_creates_public_clinic_and_doctor_and_is_idempotent(): void
    {
        $this->seedLookups();
        $path = $this->writeFixture();
        $report = storage_path('framework/testing-healthcare-import');

        $this->artisan('wizmedik:import-healthcare-master', [
            'file' => $path,
            '--force' => true,
            '--report' => $report,
        ])->assertSuccessful();

        $this->assertSame(1, Klinika::query()->count());
        $this->assertSame(1, Doktor::query()->count());

        $clinic = Klinika::query()->firstOrFail();
        $doctor = Doktor::query()->firstOrFail();

        $this->assertTrue((bool) $clinic->aktivan);
        $this->assertTrue((bool) $clinic->verifikovan);
        $this->assertNull($clinic->user_id);
        $this->assertSame('Poliklinika Test', $clinic->naziv);
        $this->assertSame('Sarajevo', $clinic->grad);

        $this->assertTrue((bool) $doctor->aktivan);
        $this->assertTrue((bool) $doctor->verifikovan);
        $this->assertFalse((bool) $doctor->prihvata_online);
        $this->assertNull($doctor->user_id);
        $this->assertSame('Pavle', $doctor->ime);
        $this->assertSame($clinic->id, $doctor->klinika_id);
        $this->assertTrue($clinic->specijalnosti()->where('slug', 'gastroenterologija')->exists());

        $this->artisan('wizmedik:import-healthcare-master', [
            'file' => $path,
            '--force' => true,
            '--report' => $report,
        ])->assertSuccessful();

        $this->assertSame(1, Klinika::query()->count());
        $this->assertSame(1, Doktor::query()->count());
    }

    public function test_claimed_profile_is_not_overwritten(): void
    {
        $this->seedLookups();
        $owner = User::query()->create([
            'name' => 'Owner Test',
            'ime' => 'Owner',
            'prezime' => 'Test',
            'email' => 'claimed-clinic@example.com',
            'password' => Hash::make('Password123!A'),
        ]);

        $clinic = Klinika::query()->create([
            'naziv' => 'Poliklinika Test',
            'adresa' => 'Stara adresa 1',
            'grad' => 'Sarajevo',
            'telefon' => '+38733111111',
            'aktivan' => true,
            'verifikovan' => true,
            'user_id' => $owner->id,
        ]);

        $path = $this->writeFixture();
        $this->artisan('wizmedik:import-healthcare-master', [
            'file' => $path,
            '--force' => true,
            '--update-existing' => true,
            '--report' => storage_path('framework/testing-healthcare-import'),
        ])->assertSuccessful();

        $clinic->refresh();
        $this->assertSame('Stara adresa 1', $clinic->adresa);
        $this->assertSame($owner->id, $clinic->user_id);
        $this->assertSame(0, Doktor::query()->where('klinika_id', $clinic->id)->count());
    }

    public function test_unknown_specialty_and_invalid_url_and_missing_fields_are_review_or_failed(): void
    {
        $this->seedLookups();
        $path = $this->writeFixture(includeProblems: true);

        $this->artisan('wizmedik:import-healthcare-master', [
            'file' => $path,
            '--dry-run' => true,
            '--report' => storage_path('framework/testing-healthcare-import'),
        ])->assertSuccessful();

        $reports = glob(storage_path('framework/testing-healthcare-import/healthcare-import-*.json'));
        $this->assertNotEmpty($reports);
        $payload = json_decode((string) file_get_contents(end($reports)), true);

        $reasons = collect($payload['review'] ?? [])->pluck('reason')->all();
        $failed = collect($payload['failed'] ?? [])->pluck('reason')->all();
        $this->assertContains('specialty_unmapped', $reasons);
        $this->assertContains('invalid_url', $reasons);
        $this->assertContains('missing_required', $failed);
        $this->assertGreaterThan(0, $payload['sheets']['01_INSTITUTIONS']['skip'] ?? 0);
    }

    public function test_laboratory_rows_are_skipped(): void
    {
        $this->seedLookups();
        $path = $this->writeFixture(includeProblems: true);

        $this->artisan('wizmedik:import-healthcare-master', [
            'file' => $path,
            '--force' => true,
            '--report' => storage_path('framework/testing-healthcare-import'),
        ])->assertSuccessful();

        $this->assertFalse(Klinika::query()->where('naziv', 'Lab Skip')->exists());
    }

    private function seedLookups(): void
    {
        Grad::query()->create([
            'naziv' => 'Sarajevo',
            'slug' => 'sarajevo',
            'u_gradu' => 'u Sarajevu',
            'opis' => 'Grad',
            'detaljni_opis' => 'Grad',
            'aktivan' => true,
        ]);

        Specijalnost::query()->create([
            'naziv' => 'Gastroenterologija',
            'slug' => 'gastroenterologija',
            'aktivan' => true,
        ]);
    }

    private function writeFixture(bool $includeProblems = false): string
    {
        $dir = storage_path('framework/testing-healthcare-import');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $path = $dir . DIRECTORY_SEPARATOR . 'fixture.xlsx';

        $spreadsheet = new Spreadsheet();

        $this->writeSheet($spreadsheet, '01_INSTITUTIONS', [
            ['Institution ID', 'Naziv', 'Tip', 'Vlasništvo', 'Grad', 'Adresa', 'Telefon', 'Telefon normalizovan', 'Email', 'Official website', 'Google Place ID', 'Confidence', 'Import action'],
            ['INST-TEST-001', 'Poliklinika Test', 'polyclinic', 'private', 'Sarajevo', 'Zmaja od Bosne 1', '+387 33 111 222', '+38733111222', 'info@test.example', 'https://test.example', 'ChIJ-test', 'high', 'import'],
        ], 0);

        if ($includeProblems) {
            $sheet = $spreadsheet->getSheetByName('01_INSTITUTIONS');
            $sheet->fromArray(['INST-TEST-LAB', 'Lab Skip', 'laboratory', 'private', 'Sarajevo', 'Adresa 2', '+387 33 000 000', '+38733000000', null, 'https://lab.example', null, 'high', 'import'], null, 'A3');
            $sheet->fromArray(['INST-TEST-BAD', 'Bez telefona', 'polyclinic', 'private', 'Sarajevo', 'Adresa 3', null, null, null, 'https://ok.example', null, 'high', 'import'], null, 'A4');
            $sheet->fromArray(['INST-TEST-URL', 'Lose URL', 'polyclinic', 'private', 'Sarajevo', 'Adresa 4', '+387 33 444 444', '+38733444444', null, 'not a url', null, 'high', 'import'], null, 'A5');
        }

        $this->addSheet($spreadsheet, '02_LOCATIONS', [
            ['Location ID', 'Institution ID', 'Ustanova', 'Adresa', 'Grad', 'Country', 'Primary'],
            ['LOC-INST-TEST-001', 'INST-TEST-001', 'Poliklinika Test', 'Zmaja od Bosne 1', 'Sarajevo', 'BA', 'YES'],
        ]);

        $this->addSheet($spreadsheet, '03_DOCTORS', [
            ['Doctor ID', 'Ime i prezime / javna titula', 'Profesionalna titula', 'Primarna specijalnost', 'Subspecijalnost / uža oblast', 'Profile URL', 'Primary source', 'Source type', 'Confidence', 'Public professional data only', 'Napomene'],
            ['DOC-TEST-001', 'dr Pavle Arambašić', 'dr', 'Gastroenterologija', 'Hepatologija', null, 'https://test.example', 'official_website', 'high', 'YES', 'public'],
        ]);

        $this->addSheet($spreadsheet, '04_DOCTOR_INSTITUTIONS', [
            ['Affiliation ID', 'Doctor ID', 'Doktor', 'Institution ID', 'Specijalnost u ustanovi', 'Uloga', 'Source URL', 'Source type', 'Confidence'],
            ['AFF-TEST-001', 'DOC-TEST-001', 'dr Pavle Arambašić', 'INST-TEST-001', 'Gastroenterologija', 'doctor', 'https://test.example', 'official_website', 'high'],
        ]);

        $specialtyRows = [
            ['Relation ID', 'Doctor ID', 'Doktor', 'Source specialty', 'Canonical candidate', 'Subspeciality', 'Mapping status', 'Source URL', 'Confidence'],
            ['DSP-DOC-TEST-001', 'DOC-TEST-001', 'dr Pavle Arambašić', 'Gastroenterologija', 'gastroenterologija', 'Hepatologija', 'mapped', 'https://test.example', 'high'],
        ];
        if ($includeProblems) {
            $specialtyRows[] = ['DSP-DOC-UNKNOWN', 'DOC-TEST-001', 'dr Pavle Arambašić', 'Kvantna medicina', 'kvantna-medicina', null, 'candidate', 'https://test.example', 'medium'];
        }
        $this->addSheet($spreadsheet, '05_DOCTOR_SPECIALITIES', $specialtyRows);

        $this->addSheet($spreadsheet, '06_INST_SPECIALITIES', [
            ['Relation ID', 'Institution ID', 'Ustanova', 'Source specialty', 'Canonical candidate', 'Mapping status', 'Source URL', 'Confidence'],
            ['ISP-INST-TEST-001', 'INST-TEST-001', 'Poliklinika Test', 'Gastroenterologija', 'gastroenterologija', 'mapped', 'https://test.example', 'high'],
        ]);

        $this->addSheet($spreadsheet, '07_INST_SERVICES', [
            ['Relation ID', 'Institution ID', 'Ustanova', 'Usluga', 'Service slug candidate', 'Source URL', 'Confidence'],
        ]);

        $this->addSheet($spreadsheet, '08_CONTACTS', [
            ['Contact ID', 'Entity type', 'Entity ID', 'Naziv', 'Contact type', 'Raw', 'Normalized', 'Primary', 'Source URL', 'Confidence'],
            ['CONT-TEST-001', 'institution', 'INST-TEST-001', 'Poliklinika Test', 'phone', '+387 33 111 222', '+38733111222', 'YES', 'https://test.example', 'high'],
        ]);

        $this->addSheet($spreadsheet, '09_WORKING_HOURS', [
            ['Hours ID', 'Institution ID', 'Ustanova', 'Working hours raw', 'Normalized status', 'Source URL', 'Confidence'],
            ['HRS-INST-TEST-001', 'INST-TEST-001', 'Poliklinika Test', 'Pon-Pet 08:00-17:00; Sub 08:00-14:00', 'raw_string_requires_structured_parse', 'https://test.example', 'high'],
        ]);

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function writeSheet(Spreadsheet $spreadsheet, string $name, array $rows, int $index): void
    {
        $sheet = $spreadsheet->getSheet($index);
        $sheet->setTitle($name);
        $sheet->fromArray($rows, null, 'A1');
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function addSheet(Spreadsheet $spreadsheet, string $name, array $rows): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($name);
        $sheet->fromArray($rows, null, 'A1');
    }
}
