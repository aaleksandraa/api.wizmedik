<?php

namespace Tests\Unit\HealthcareImport;

use App\Services\HealthcareImport\Normalizers\DoctorNameNormalizer;
use App\Services\HealthcareImport\Normalizers\PhoneNormalizer;
use App\Services\HealthcareImport\Normalizers\UrlNormalizer;
use App\Services\HealthcareImport\Normalizers\WorkingHoursParser;
use PHPUnit\Framework\TestCase;

class HealthcareImportNormalizerTest extends TestCase
{
    public function test_doctor_name_strips_titles_and_keeps_bosnian_letters(): void
    {
        $parsed = (new DoctorNameNormalizer())->parse('dr. Pavle Arambašić');

        $this->assertSame('Pavle', $parsed['ime']);
        $this->assertSame('Arambašić', $parsed['prezime']);
        $this->assertStringContainsString('š', $parsed['match_key']);
        $this->assertSame('pavle arambasic', $parsed['ascii_key']);
    }

    public function test_phone_normalizer_adds_country_code(): void
    {
        $this->assertSame('+38733467444', (new PhoneNormalizer())->normalize('+387 33 467 444'));
        $this->assertSame('+38733467444', (new PhoneNormalizer())->normalize('033 467 444'));
    }

    public function test_url_normalizer_rejects_invalid_and_accepts_https(): void
    {
        $urls = new UrlNormalizer();
        $this->assertSame('https://poliklinika-atrijum.ba/', $urls->normalize('https://poliklinika-atrijum.ba/'));
        $this->assertNull($urls->normalize('not a url'));
        $this->assertSame('poliklinika-atrijum.ba', $urls->domain('https://www.poliklinika-atrijum.ba/'));
    }

    public function test_working_hours_parser_handles_weekday_range(): void
    {
        $hours = (new WorkingHoursParser())->parse('Pon-Pet 08:00-17:00; Sub 08:00-14:00');

        $this->assertIsArray($hours);
        $this->assertFalse($hours['ponedeljak']['closed']);
        $this->assertSame('08:00', $hours['ponedeljak']['open']);
        $this->assertSame('17:00', $hours['petak']['close']);
        $this->assertSame('14:00', $hours['subota']['close']);
        $this->assertTrue($hours['nedjelja']['closed']);
    }

    public function test_working_hours_parser_returns_null_for_ambiguous_text(): void
    {
        $this->assertNull((new WorkingHoursParser())->parse('službeni header; kontakt stranica prikazuje i 09:00-17:00'));
    }

    public function test_all_caps_registry_name_is_title_cased(): void
    {
        $parsed = (new DoctorNameNormalizer())->parse('ABDIBEGOVIĆ DŽENITA');

        $this->assertSame('Abdibegović', $parsed['ime']);
        $this->assertSame('Dženita', $parsed['prezime']);
    }
}
