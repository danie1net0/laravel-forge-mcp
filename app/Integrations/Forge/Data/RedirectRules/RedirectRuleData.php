<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\RedirectRules;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class RedirectRuleData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public int $siteId,
        public string $from,
        public string $to,
        public string $type,
        public string $status,
        public string $createdAt,
    ) {
    }
}
