<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Firewall;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CreateFirewallRuleData extends Data
{
    public function __construct(
        public string $name,
        public int|string $port,
        public ?string $ipAddress = null,
    ) {
    }
}
