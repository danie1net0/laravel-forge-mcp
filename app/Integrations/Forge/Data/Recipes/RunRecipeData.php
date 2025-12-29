<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Recipes;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
class RunRecipeData extends Data
{
    /**
     * @param  int[]  $servers
     */
    public function __construct(
        public array $servers,
    ) {
    }
}
