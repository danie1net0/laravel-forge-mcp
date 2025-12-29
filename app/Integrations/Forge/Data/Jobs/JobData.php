<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Jobs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class JobData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public string $command,
        public string $user,
        public string $frequency,
        public string $cron,
        public string $status,
        public string $createdAt,
    ) {
    }
}
