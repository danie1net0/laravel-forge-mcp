<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Recipes;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Recipes\RunRecipeData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class RunRecipeTool extends Tool
{
    protected string $description = 'Run a recipe on one or more servers.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'recipe_id' => ['required', 'integer', 'min:1'],
            'servers' => ['required', 'array'],
        ]);

        $recipeId = $request->integer('recipe_id');
        $data = $request->only('servers');

        try {
            $runData = RunRecipeData::from($data);
            $client->recipes()->run($recipeId, $runData);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Recipe execution started',
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'recipe_id' => $schema->integer()->min(1)->required(),
            'servers' => $schema->array()->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
