<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Recipes\{CreateRecipeData, RecipeCollectionData, RecipeData, RunRecipeData, UpdateRecipeData};
use App\Integrations\Forge\Requests\Recipes\{CreateRecipeRequest, DeleteRecipeRequest, GetRecipeRequest, ListRecipesRequest, RunRecipeRequest, UpdateRecipeRequest};

class RecipeResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(): RecipeCollectionData
    {
        $request = new ListRecipesRequest();
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $recipeId): RecipeData
    {
        $request = new GetRecipeRequest($recipeId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(CreateRecipeData $data): RecipeData
    {
        $request = new CreateRecipeRequest($data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function update(int $recipeId, UpdateRecipeData $data): RecipeData
    {
        $request = new UpdateRecipeRequest($recipeId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $recipeId): void
    {
        $this->connector->send(new DeleteRecipeRequest($recipeId));
    }

    public function run(int $recipeId, RunRecipeData $data): void
    {
        $this->connector->send(new RunRecipeRequest($recipeId, $data));
    }
}
