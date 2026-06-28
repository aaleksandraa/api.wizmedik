<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDatabaseBackupController extends Controller
{
    public function status()
    {
        $destination = $this->getBackupDestination()->fresh();

        $backups = $destination->backups()->map(function (Backup $backup) {
            return $this->serializeBackup($backup);
        })->values();

        return response()->json([
            'schedule' => [
                'command' => 'backup:run --only-db',
                'time' => '02:00',
                'timezone' => config('app.timezone', 'Europe/Sarajevo'),
                'cleanup_time' => '02:30',
                'cleanup_command' => 'backup:clean',
                'log_file' => 'storage/logs/database-backup-schedule.log',
                'retention' => [
                    'keep_all_days' => config('backup.cleanup.default_strategy.keep_all_backups_for_days'),
                    'keep_daily_days' => config('backup.cleanup.default_strategy.keep_daily_backups_for_days'),
                    'keep_weekly_weeks' => config('backup.cleanup.default_strategy.keep_weekly_backups_for_weeks'),
                    'keep_monthly_months' => config('backup.cleanup.default_strategy.keep_monthly_backups_for_months'),
                ],
            ],
            'storage' => [
                'disk' => $destination->diskName(),
                'backup_name' => $destination->backupName(),
                'reachable' => $destination->isReachable(),
                'used_bytes' => (int) $destination->usedStorage(),
            ],
            'backups' => $backups,
            'newest_backup' => $backups->first(),
        ]);
    }

    public function download(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'path' => 'required|string',
        ]);

        $path = base64_decode($validated['path'], true);
        if ($path === false || $path === '') {
            abort(422, 'Neispravan identifikator backupa.');
        }

        $backup = $this->findBackupByPath($path);
        if (! $backup) {
            abort(404, 'Backup nije pronađen.');
        }

        $stream = $backup->stream();
        $filename = basename($backup->path());

        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function run()
    {
        $exitCode = Artisan::call('backup:run', ['--only-db' => true]);

        if ($exitCode !== 0) {
            return response()->json([
                'message' => 'Kreiranje backupa nije uspjelo.',
                'output' => trim(Artisan::output()),
            ], 500);
        }

        $newest = $this->getBackupDestination()->fresh()->newestBackup();

        if (! $newest) {
            return response()->json([
                'message' => 'Backup je pokrenut ali fajl nije pronađen na disku.',
                'output' => trim(Artisan::output()),
            ], 500);
        }

        return response()->json([
            'message' => 'Backup uspješno kreiran.',
            'backup' => $this->serializeBackup($newest),
            'output' => trim(Artisan::output()),
        ]);
    }

    private function getBackupDestination(): BackupDestination
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $backupName = (string) config('backup.backup.name');

        return BackupDestination::create($diskName, $backupName);
    }

    private function findBackupByPath(string $path): ?Backup
    {
        foreach ($this->getBackupDestination()->fresh()->backups() as $backup) {
            if ($backup->path() === $path) {
                return $backup;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function serializeBackup(Backup $backup): array
    {
        return [
            'path' => base64_encode($backup->path()),
            'filename' => basename($backup->path()),
            'date' => $backup->date()->toIso8601String(),
            'size_bytes' => (int) $backup->sizeInBytes(),
            'size_human' => $this->formatBytes($backup->sizeInBytes()),
        ];
    }

    private function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2).' '.$units[$index];
    }
}
