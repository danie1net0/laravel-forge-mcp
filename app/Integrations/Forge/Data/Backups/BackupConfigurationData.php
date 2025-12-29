<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Backups;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class BackupConfigurationData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public ?int $dayOfWeek,
        public string $time,
        public string $provider,
        public string $providerName,
        public ?string $lastBackupTime,
        public string $createdAt,
    ) {
    }
}
