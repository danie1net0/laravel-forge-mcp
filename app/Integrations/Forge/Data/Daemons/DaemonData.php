<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Daemons;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class DaemonData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public ?string $command = null,
        public ?string $user = null,
        public ?string $status = null,
        public ?string $directory = null,
        public ?string $createdAt = null,
    ) {
    }
}
