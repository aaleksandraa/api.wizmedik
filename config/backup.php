<?php

return [
    /*
     * Backup configuration
     */
    'backup' => [
        /*
         * The name of this application. You can use this name to monitor
         * the backups.
         */
        'name' => env('APP_NAME', 'zdravlje-bih'),

        'source' => [
            'files' => [
                /*
                 * The list of directories and files that will be included in the backup.
                 */
                'include' => [
                    storage_path('app/public'),
                    base_path('.env'),
                ],

                /*
                 * These directories and files will be excluded from the backup.
                 */
                'exclude' => [
                    storage_path('app/public/temp'),
                    storage_path('framework/cache'),
                    storage_path('framework/sessions'),
                    storage_path('framework/views'),
                    storage_path('logs'),
                ],

                /*
                 * Determines if symlinks should be followed.
                 */
                'follow_links' => false,

                /*
                 * Determines if unreadable directories should be ignored.
                 */
                'ignore_unreadable_directories' => false,

                /*
                 * The path where the files should be stored relative to the root of the zip file.
                 */
                'relative_path' => null,
            ],

            /*
             * The names of the connections to the databases that should be backed up.
             */
            'databases' => [
                'pgsql',
            ],
        ],

        /*
         * The database dump can be compressed to decrease disk space usage.
         */
        'database_dump_compressor' => null,

        /*
         * The destination where the backups will be stored.
         */
        'destination' => [
            /*
             * The filename prefix used for the backup zip file.
             */
            'filename_prefix' => '',

            /*
             * The disk names on which the backups will be stored.
             */
            'disks' => [
                env('BACKUP_DISK', 'local'),
            ],
        ],

        /*
         * The directory where the temporary files will be stored.
         */
        'temporary_directory' => storage_path('app/backup-temp'),
    ],

    /*
     * You can get notified when specific events occur.
     */
    'notifications' => [
        /*
         * Notifications will be sent to these email addresses.
         */
        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL', 'admin@example.com'),
        ],

        /*
         * Here you can specify which events should trigger a notification.
         */
        'events' => [
            'backup_successful' => true,
            'backup_failed' => true,
            'cleanup_successful' => false,
            'cleanup_failed' => true,
        ],
    ],

    /*
     * Here you can specify which backups should be monitored.
     */
    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'zdravlje-bih'),
            'disks' => [env('BACKUP_DISK', 'local')],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    'cleanup' => [
        /*
         * The strategy that will be used to cleanup old backups.
         */
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            /*
             * The number of days for which backups must be kept.
             */
            'keep_all_backups_for_days' => 7,

            /*
             * The number of days for which daily backups must be kept.
             */
            'keep_daily_backups_for_days' => 16,

            /*
             * The number of weeks for which one weekly backup must be kept.
             */
            'keep_weekly_backups_for_weeks' => 8,

            /*
             * The number of months for which one monthly backup must be kept.
             */
            'keep_monthly_backups_for_months' => 4,

            /*
             * The number of years for which one yearly backup must be kept.
             */
            'keep_yearly_backups_for_years' => 2,

            /*
             * After cleaning up the backups remove the oldest backup until
             * this amount of megabytes has been reached.
             */
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
    ],
];
