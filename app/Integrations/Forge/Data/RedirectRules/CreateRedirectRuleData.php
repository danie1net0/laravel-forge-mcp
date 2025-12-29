<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\RedirectRules;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CreateRedirectRuleData extends Data
{
    public function __construct(
        public string $from,
        public string $to,
        public ?string $type = null,
    ) {
    }
}
