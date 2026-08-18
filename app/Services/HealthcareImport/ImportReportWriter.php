<?php

namespace App\Services\HealthcareImport;

class ImportReportWriter
{
    /**
     * @return array{json:string,csv:string}
     */
    public function write(ImportContext $context, string $directory): array
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException("Ne mogu kreirati report direktorij: {$directory}");
        }

        $result = $context->result;
        $payload = [
            'uuid' => $result->uuid,
            'dry_run' => $context->dryRun,
            'source' => $context->source,
            'filename' => $context->filename,
            'sheets' => $result->sheets,
            'totals' => $result->totals(),
            'review' => $result->reviewRows,
            'failed' => $result->failedRows,
        ];

        $jsonPath = $directory . DIRECTORY_SEPARATOR . "healthcare-import-{$result->uuid}.json";
        file_put_contents(
            $jsonPath,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $csvPath = $directory . DIRECTORY_SEPARATOR . "healthcare-import-{$result->uuid}.csv";
        $handle = fopen($csvPath, 'wb');
        if ($handle === false) {
            throw new \RuntimeException("Ne mogu pisati CSV report: {$csvPath}");
        }

        fputcsv($handle, ['sheet', 'row', 'external_id', 'action', 'status', 'reason', 'candidates']);
        foreach (array_merge($result->reviewRows, $result->failedRows) as $row) {
            fputcsv($handle, [
                $row['sheet'] ?? '',
                $row['row'] ?? '',
                $row['external_id'] ?? '',
                $row['action'] ?? '',
                $row['status'] ?? '',
                $row['reason'] ?? '',
                json_encode($row['candidates'] ?? [], JSON_UNESCAPED_UNICODE),
            ]);
        }
        fclose($handle);

        $result->jsonPath = $jsonPath;
        $result->csvPath = $csvPath;

        return ['json' => $jsonPath, 'csv' => $csvPath];
    }
}
