<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Recipes;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use App\Integrations\Forge\Data\Recipes\RecipeData;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class ListRecipesTool extends Tool
{
    protected string $description = 'List all recipes (reusable server provisioning scripts) in your Forge account.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        try {
            $recipes = $client->recipes()->list()->recipes;

            $formatted = array_map(fn (RecipeData $recipe): array => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'user' => $recipe->user,
                'created_at' => $recipe->createdAt,
            ], $recipes);

            return Response::text(json_encode([
                'success' => true,
                'recipes' => $formatted,
                'count' => count($formatted),
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
