<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Recipes;

use App\Integrations\Forge\Data\Recipes\RecipeData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetRecipeRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $recipeId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/recipes/{$this->recipeId}";
    }

    public function createDtoFromResponse(Response $response): RecipeData
    {
        return RecipeData::from($response->json('recipe'));
    }
}
