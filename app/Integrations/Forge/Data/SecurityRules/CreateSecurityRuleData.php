<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\SecurityRules;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CreateSecurityRuleData extends Data
{
    public function __construct(
        public string $name,
        public string $path,
        public ?array $credentials = null,
    ) {
    }
}
