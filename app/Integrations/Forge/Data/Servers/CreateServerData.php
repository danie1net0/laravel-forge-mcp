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
        public string $name,
        public string $provider,
        public string $type,
        public string $ubuntuVersion,
        public ?int $credentialId = null,
        public ?int $teamId = null,
        public ?string $phpVersion = null,
        public ?string $databaseType = null,
        public ?int $recipeId = null,
        /** @var array<int, string>|null */
        public ?array $tags = null,
        public ?bool $addKeyToSourceControl = null,
        public ?string $database = null,
        /** @var array<string, mixed>|null */
        public ?array $ocean2 = null,
        /** @var array<string, mixed>|null */
        public ?array $aws = null,
        /** @var array<string, mixed>|null */
        public ?array $hetzner = null,
        /** @var array<string, mixed>|null */
        public ?array $vultr = null,
        /** @var array<string, mixed>|null */
        public ?array $akamai = null,
        /** @var array<string, mixed>|null */
        public ?array $laravel = null,
        /** @var array<string, mixed>|null */
        public ?array $custom = null,
    ) {
    }
}
