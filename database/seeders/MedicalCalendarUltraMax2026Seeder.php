<?php

namespace Database\Seeders;

use App\Models\MedicalCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MedicalCalendarUltraMax2026Seeder extends Seeder
{
    public function run(): void
    {
        $xmlPath = public_path('medical_calendar_2026_ULTRA_MAX.xml');

        if (!File::exists($xmlPath)) {
            throw new \RuntimeException("XML file not found: {$xmlPath}");
        }

        if (!function_exists('simplexml_load_file')) {
            throw new \RuntimeException('SimpleXML extension is not enabled (php-xml).');
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($xmlPath, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xml === false) {
            $errors = array_map(
                static fn ($error) => trim($error->message),
                libxml_get_errors()
            );
            libxml_clear_errors();

            $message = count($errors) > 0 ? implode(' | ', array_unique($errors)) : 'Unknown XML parse error.';
            throw new \RuntimeException("Invalid XML: {$message}");
        }

        libxml_clear_errors();

        $rawEvents = $this->extractEvents($xml);

        if (count($rawEvents) === 0) {
            $this->command?->warn('No events found in medical_calendar_2026_ULTRA_MAX.xml');
            return;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($rawEvents, &$created, &$updated, &$skipped): void {
            foreach ($rawEvents as $index => $rawEvent) {
                $rowNumber = $index + 1;
                $row = $this->mapEventToRow($rawEvent, $index);

                if ($row === null) {
                    $skipped++;
                    $this->command?->warn("Skipping invalid row {$rowNumber} from XML.");
                    continue;
                }

                $existing = MedicalCalendar::whereDate('date', $row['date'])
                    ->where('title', $row['title'])
                    ->where('type', $row['type'])
                    ->first();

                if ($existing) {
                    $existing->update($row);
                    $updated++;
                    continue;
                }

                MedicalCalendar::create($row);
                $created++;
            }
        });

        $this->command?->info("Medical calendar seeding done. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}.");
    }

    /**
     * @return array<int, \SimpleXMLElement>
     */
    private function extractEvents(\SimpleXMLElement $xml): array
    {
        $events = [];

        if (isset($xml->event)) {
            foreach ($xml->event as $event) {
                $events[] = $event;
            }
            return $events;
        }

        if (isset($xml->item)) {
            foreach ($xml->item as $event) {
                $events[] = $event;
            }
            return $events;
        }

        foreach ($xml->children() as $child) {
            $events[] = $child;
        }

        return $events;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapEventToRow(\SimpleXMLElement $rawEvent, int $index): ?array
    {
        $date = trim((string) ($rawEvent->date ?? ''));
        $title = trim((string) ($rawEvent->title ?? ''));
        $description = trim((string) ($rawEvent->description ?? '')) ?: null;
        $type = trim((string) ($rawEvent->type ?? '')) ?: 'day';
        $endDate = trim((string) ($rawEvent->end_date ?? '')) ?: null;
        $category = trim((string) ($rawEvent->category ?? '')) ?: null;
        $color = trim((string) ($rawEvent->color ?? '')) ?: '#0891b2';
        $isActive = $this->parseBoolValue((string) ($rawEvent->is_active ?? '1'));
        $sortOrderRaw = (string) ($rawEvent->sort_order ?? '');
        $sortOrder = is_numeric($sortOrderRaw) ? (int) $sortOrderRaw : $index;

        if ($date === '' || $title === '') {
            return null;
        }

        if (!$this->isValidDate($date)) {
            return null;
        }

        $allowedTypes = ['day', 'week', 'month', 'campaign'];
        if (!in_array($type, $allowedTypes, true)) {
            return null;
        }

        if ($endDate !== null) {
            if (!$this->isValidDate($endDate) || $endDate < $date) {
                $endDate = null;
            }
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#0891b2';
        }

        return [
            'date' => $date,
            'title' => $title,
            'description' => $description,
            'type' => $type,
            'end_date' => $endDate,
            'category' => $category,
            'color' => $color,
            'is_active' => $isActive,
            'sort_order' => $sortOrder,
        ];
    }

    private function parseBoolValue(string $value): bool
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, ['1', 'true', 'yes', 'da'], true);
    }

    private function isValidDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}

