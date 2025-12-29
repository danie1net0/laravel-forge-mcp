<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\SecurityRules;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class SecurityRuleData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public int $siteId,
        public string $name,
        public string $path,
        public string $credentials,
        public string $createdAt,
    ) {
    }
}
