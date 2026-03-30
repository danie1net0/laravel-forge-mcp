<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Databases;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class DatabaseUserData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public ?string $name = null,
        public ?string $status = null,
        public ?string $createdAt = null,
        /** @var array<int>|null */
        public ?array $databases = null,
    ) {
    }
}
