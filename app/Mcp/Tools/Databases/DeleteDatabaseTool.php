<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Databases;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteDatabaseTool extends Tool
{
    protected string $description = '⚠️ PERMANENTLY DELETE a database and ALL ITS DATA. This CANNOT be undone. Requires server_id and database_id.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'database_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $client->databases()->delete(
                $request->integer('server_id'),
                $request->integer('database_id')
            );

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Database PERMANENTLY DELETED. All data is lost.',
                'warning' => 'This action is IRREVERSIBLE',
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
        return [
            'server_id' => $schema->integer()->min(1)->required(),
            'database_id' => $schema->integer()->min(1)->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
