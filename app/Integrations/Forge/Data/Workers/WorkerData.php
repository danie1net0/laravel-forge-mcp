<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Workers;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class WorkerData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public int $siteId,
        public string $connection,
        public string $command,
        public string $queue,
        public int $timeout,
        public int $sleep,
        public int $tries,
        public string $environment,
        public int $daemon,
        public string $status,
        public string $createdAt,
    ) {
    }
}
