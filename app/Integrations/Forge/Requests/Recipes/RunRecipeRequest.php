<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Recipes;

use App\Integrations\Forge\Data\Recipes\RunRecipeData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class RunRecipeRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $recipeId,
        private readonly RunRecipeData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/recipes/{$this->recipeId}/run";
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
