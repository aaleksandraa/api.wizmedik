<?php

namespace App\Services\Seo;

class SeoPrerenderPolicy
{
    public static function shouldPublish(int $statusCode): bool
    {
        return $statusCode < 400;
    }

    public static function allowsStaleDeletion(bool $fullReconciliation, bool $pathCollectionFailed, int $writeErrors): bool
    {
        return $fullReconciliation && !$pathCollectionFailed && $writeErrors === 0;
    }
}
