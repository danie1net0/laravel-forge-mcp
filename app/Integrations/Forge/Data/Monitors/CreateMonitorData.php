<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Monitors;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CreateMonitorData extends Data
{
    public function __construct(
        public string $type,
        public ?string $operator = null,
        public ?int $threshold = null,
        public ?int $minutes = null,
    ) {
    }
}
