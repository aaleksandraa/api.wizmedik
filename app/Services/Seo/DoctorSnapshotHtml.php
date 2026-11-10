<?php

namespace App\Services\Seo;

class DoctorSnapshotHtml
{
    /**
     * Public summary for one doctor. Returns an empty string when the name is incomplete.
     * Phone, email and other private contact fields are never rendered.
     */
    public static function render(object $doctor, string $profileUrl, string $listingUrl, string $slug): string
    {
        $firstName = trim((string) ($doctor->ime ?? ''));
        $lastName = trim((string) ($doctor->prezime ?? ''));
        if ($firstName === '' || $lastName === '') {
            return '';
        }

        $name = trim('Dr. ' . $firstName . ' ' . $lastName);
        $specialty = trim((string) ($doctor->specijalnost ?? ''));
        $city = trim((string) ($doctor->grad ?? ''));
        $summary = trim(strip_tags((string) ($doctor->opis ?? '')));
        if ($summary === '') {
            $summary = trim($specialty . ($city !== '' ? ' u ' . $city : ''));
        }
        if (mb_strlen($summary) > 320) {
            $summary = rtrim(mb_substr($summary, 0, 317)) . '...';
        }

        $facts = array_values(array_filter([$specialty, $city], static fn (string $value): bool => $value !== ''));
        $factLine = $facts !== [] ? implode(' · ', $facts) : 'Profil doktora';

        $nameHtml = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $factHtml = htmlspecialchars($factLine, ENT_QUOTES, 'UTF-8');
        $summaryHtml = htmlspecialchars($summary, ENT_QUOTES, 'UTF-8');
        $urlHtml = htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8');
        $slugHtml = htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
        $listingHtml = htmlspecialchars($listingUrl, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<main id="seo-snapshot" data-doctor-slug="{$slugHtml}">
      <article>
        <h1>{$nameHtml}</h1>
        <p>{$factHtml}</p>
        <p>{$summaryHtml}</p>
        <p><a href="{$urlHtml}">Profil doktora</a> · <a href="{$listingHtml}">Doktori</a></p>
      </article>
    </main>
HTML;
    }
}
