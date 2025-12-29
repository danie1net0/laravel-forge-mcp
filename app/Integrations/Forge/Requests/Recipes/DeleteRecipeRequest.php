<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Recipes;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteRecipeRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $recipeId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/recipes/{$this->recipeId}";
    }
}
