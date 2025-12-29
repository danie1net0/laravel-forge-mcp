<?php

namespace App\Integrations\Forge\Data\Sites;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CreateSiteData extends Data
{
    public function __construct(
        public string $domain,
        public string $projectType,
        public ?array $aliases = null,
        public ?string $directory = null,
        public ?bool $isolated = null,
        public ?string $username = null,
        public ?string $database = null,
        public ?string $phpVersion = null,
        public ?int $nginxTemplate = null,
    ) {
    }
}
