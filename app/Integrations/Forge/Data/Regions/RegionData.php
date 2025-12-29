<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Regions;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class RegionData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        /** @var array<string, array{id: string, name: string}> */
        public array $sizes,
    ) {
    }
}
