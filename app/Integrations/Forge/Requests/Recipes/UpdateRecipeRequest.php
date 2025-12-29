<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Recipes;

use App\Integrations\Forge\Data\Recipes\{RecipeData, UpdateRecipeData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class UpdateRecipeRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        private readonly int $recipeId,
        private readonly UpdateRecipeData $data,
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

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return array_filter(
            $this->data->toArray(),
            fn (mixed $value): bool => $value !== null
        );
    }
}
