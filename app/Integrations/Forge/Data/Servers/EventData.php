<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Servers;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class EventData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public string $runAs,
        public string $description,
        public string $status,
        public string $createdAt,
        public ?string $output = null,
    ) {
    }
}
