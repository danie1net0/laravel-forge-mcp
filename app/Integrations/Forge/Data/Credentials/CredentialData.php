<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Credentials;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CredentialData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $type,
    ) {
    }
}
