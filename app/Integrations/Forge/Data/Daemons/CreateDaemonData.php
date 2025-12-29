<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Daemons;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CreateDaemonData extends Data
{
    public function __construct(
        public string $command,
        public string $directory,
        public string $user = 'forge',
        public ?int $processes = null,
        public ?int $startsecs = null,
    ) {
    }
}
