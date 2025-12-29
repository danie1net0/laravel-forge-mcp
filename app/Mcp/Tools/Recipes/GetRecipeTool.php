<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Recipes;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class GetRecipeTool extends Tool
{
    protected string $description = 'Get detailed information about a specific recipe.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate(['recipe_id' => ['required', 'integer', 'min:1']]);

        try {
            $recipe = $client->recipes()->get($request->integer('recipe_id'));

            return Response::text(json_encode([
                'success' => true,
                'recipe' => [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'user' => $recipe->user,
                    'created_at' => $recipe->createdAt,
                ],
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
