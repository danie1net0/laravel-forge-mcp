<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Recipes;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class RecipeData extends Data
{
    public function __construct(
        public int $id,
        public ?string $key,
        public string $name,
        public string $user,
        public string $createdAt,
    ) {
    }
}
