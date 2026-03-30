<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Php;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class PhpVersionData extends Data
{
    public function __construct(
        public ?string $version = null,
        public ?string $displayableVersion = null,
        public ?string $status = null,
        public ?bool $usedAsDefault = null,
        public ?bool $usedOnCli = null,
    ) {
    }
}
