<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Servers;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class UpdateServerData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $size = null,
        public ?string $ipAddress = null,
        public ?string $privateIpAddress = null,
        public ?int $maxUploadSize = null,
        public ?array $network = null,
    ) {
    }
}
