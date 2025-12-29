<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Workers;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CreateWorkerData extends Data
{
    public function __construct(
        public string $connection,
        public string $queue,
        public ?int $timeout = null,
        public ?int $sleep = null,
        public ?int $tries = null,
        public ?bool $daemon = null,
        public ?bool $force = null,
    ) {
    }
}
