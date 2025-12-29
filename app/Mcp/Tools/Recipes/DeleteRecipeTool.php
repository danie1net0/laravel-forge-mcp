<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Recipes;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteRecipeTool extends Tool
{
    protected string $description = 'Delete a recipe from your Forge account.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate(['recipe_id' => ['required', 'integer', 'min:1']]);

        $recipeId = $request->integer('recipe_id');

        try {
            $client->recipes()->delete($recipeId);

            return Response::text(json_encode([
                'success' => true,
                'message' => "Recipe #{$recipeId} deleted successfully",
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return ['recipe_id' => $schema->integer()->min(1)->required()];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
