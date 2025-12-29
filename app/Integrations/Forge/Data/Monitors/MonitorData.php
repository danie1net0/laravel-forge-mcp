<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Monitors;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class MonitorData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public string $status,
        public string $type,
        public string $operator,
        public int $threshold,
        public int $minutes,
        public string $state,
        public string $stateChangedAt,
    ) {
    }
}
