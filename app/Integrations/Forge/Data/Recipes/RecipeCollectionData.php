<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Recipes;

use Spatie\LaravelData\Data;

class RecipeCollectionData extends Data
{
    /**
     * @param  RecipeData[]  $recipes
     */
    public function __construct(
        public array $recipes,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $recipes = array_map(
            fn (array $recipe): RecipeData => RecipeData::from($recipe),
            $data['recipes'] ?? []
        );

        return new self(recipes: $recipes);
    }
}
