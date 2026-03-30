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
        public ?string $connection = null,
        public ?string $command = null,
        public ?string $queue = null,
        public ?int $timeout = null,
        public ?int $sleep = null,
        public ?int $tries = null,
        public ?string $environment = null,
        public ?int $daemon = null,
        public ?string $status = null,
        public ?string $createdAt = null,
    ) {
    }
}
