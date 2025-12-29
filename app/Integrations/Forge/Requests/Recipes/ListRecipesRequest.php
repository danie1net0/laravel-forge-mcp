<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Recipes;

use App\Integrations\Forge\Data\Recipes\RecipeCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListRecipesRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/recipes';
    }

    public function createDtoFromResponse(Response $response): RecipeCollectionData
    {
        return RecipeCollectionData::fromResponse($response->json());
    }
}
