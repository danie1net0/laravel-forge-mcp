<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Backups;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class BackupData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public int $backupConfigurationId,
        public string $status,
        public string $restoreStatus,
        public string $archivePath,
        public int $size,
        public string $uuid,
        public string $duration,
        public ?string $lastBackupTime,
        public string $createdAt,
    ) {
    }
}
