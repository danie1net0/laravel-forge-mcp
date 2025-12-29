<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Jobs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CreateJobData extends Data
{
    public function __construct(
        public string $command,
        public string $frequency,
        public string $user = 'forge',
        public ?string $minute = null,
        public ?string $hour = null,
        public ?string $day = null,
        public ?string $month = null,
        public ?string $weekday = null,
    ) {
    }
}
