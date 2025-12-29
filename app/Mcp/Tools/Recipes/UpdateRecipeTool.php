<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Recipes;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Recipes\UpdateRecipeData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class UpdateRecipeTool extends Tool
{
    protected string $description = 'Update an existing recipe.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate(['recipe_id' => ['required', 'integer', 'min:1']]);

        $recipeId = $request->integer('recipe_id');
        $data = $request->except('recipe_id');

        try {
            $updateData = UpdateRecipeData::from($data);
            $recipe = $client->recipes()->update($recipeId, $updateData);

            return Response::text(json_encode([
                'success' => true,
                'recipe' => ['id' => $recipe->id, 'name' => $recipe->name],
                'message' => 'Recipe updated successfully',
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'recipe_id' => $schema->integer()->min(1)->required(),
            'name' => $schema->string(),
            'user' => $schema->string(),
            'script' => $schema->string(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
