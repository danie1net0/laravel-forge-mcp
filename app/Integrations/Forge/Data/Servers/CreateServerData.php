<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Servers;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CreateServerData extends Data
{
    public function __construct(
        public int $credentialId,
        public string $name,
        public string $size,
        public string $region,
        public ?string $phpVersion = null,
        public ?string $database = null,
        public ?string $databaseName = null,
        public ?bool $loadBalancer = null,
        public ?array $network = null,
    ) {
    }
}
