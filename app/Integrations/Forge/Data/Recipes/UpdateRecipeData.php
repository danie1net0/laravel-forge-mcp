<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Recipes;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class UpdateRecipeData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $user = null,
        public ?string $script = null,
    ) {
    }
}
