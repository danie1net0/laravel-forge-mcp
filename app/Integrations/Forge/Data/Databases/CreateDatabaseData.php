<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Databases;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CreateDatabaseData extends Data
{
    public function __construct(
        public string $name,
        public ?string $user = null,
        public ?string $password = null,
    ) {
    }
}
