<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Recipes;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Recipes\CreateRecipeData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateRecipeTool extends Tool
{
    protected string $description = 'Create a new recipe (reusable server provisioning script).';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'name' => ['required', 'string'],
            'user' => ['required', 'string'],
            'script' => ['required', 'string'],
        ]);

        try {
            $createData = CreateRecipeData::from($request->all());
            $recipe = $client->recipes()->create($createData);

            return Response::text(json_encode([
                'success' => true,
                'recipe' => ['id' => $recipe->id, 'name' => $recipe->name],
                'message' => 'Recipe created successfully',
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'user' => $schema->string()->required(),
            'script' => $schema->string()->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
