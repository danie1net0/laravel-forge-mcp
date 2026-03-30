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
        public ?string $status = null,
        public ?string $restoreStatus = null,
        public ?string $archivePath = null,
        public ?int $size = null,
        public ?string $uuid = null,
        public ?string $duration = null,
        public ?string $lastBackupTime = null,
        public ?string $createdAt = null,
    ) {
    }
}
