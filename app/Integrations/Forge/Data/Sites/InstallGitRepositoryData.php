<?php

namespace App\Integrations\Forge\Data\Sites;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class InstallGitRepositoryData extends Data
{
    public function __construct(
        public string $provider,
        public string $repository,
        public ?string $branch = null,
        public ?bool $composer = null,
    ) {
    }
}
