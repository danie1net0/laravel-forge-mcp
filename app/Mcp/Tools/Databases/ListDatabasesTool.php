<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Databases;

use App\Services\ForgeService;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Exception;

#[IsReadOnly]
class ListDatabasesTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all databases on a specific Laravel Forge server.

        Returns a list of databases including:
        - Database ID
        - Name
        - Status
        - Created date

        This is a read-only operation and will not modify any databases.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
    MARKDOWN;

    public function handle(Request $request, ForgeService $forge): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $databases = $forge->listDatabases($serverId);

            $formatted = array_map(fn ($db) => [
                'id' => $db->id,
                'name' => $db->name,
                'status' => $db->status ?? null,
                'created_at' => $db->createdAt ?? null,
            ], $databases);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'count' => count($formatted),
                'databases' => $formatted,
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
            'server_id' => $schema->integer()
                ->description('The unique ID of the Forge server')
                ->minimum(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
