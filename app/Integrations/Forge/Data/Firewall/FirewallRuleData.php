<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Firewall;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class FirewallRuleData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public ?string $name = null,
        public ?int $port = null,
        public ?string $ipAddress = null,
        public ?string $status = null,
        public ?string $createdAt = null,
    ) {
    }
}
