<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Recipes;

use App\Integrations\Forge\Data\Recipes\{CreateRecipeData, RecipeData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class CreateRecipeRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly CreateRecipeData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/recipes';
    }

    public function createDtoFromResponse(Response $response): RecipeData
    {
        return RecipeData::from($response->json('recipe'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
