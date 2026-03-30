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
        public ?string $status = null,
        public ?string $type = null,
        public ?string $operator = null,
        public ?int $threshold = null,
        public ?int $minutes = null,
        public ?string $state = null,
        public ?string $stateChangedAt = null,
    ) {
    }
}
