<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Backups;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CreateBackupConfigurationData extends Data
{
    public function __construct(
        public string $provider,
        public ?string $providerName = null,
        public ?string $dayOfWeek = null,
        public ?string $time = null,
    ) {
    }
}
