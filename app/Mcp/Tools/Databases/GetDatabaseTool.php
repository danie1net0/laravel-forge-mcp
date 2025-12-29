<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Databases;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly]
#[IsIdempotent]
class GetDatabaseTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get detailed information about a specific database on a Laravel Forge server.

        Returns complete database information including:
        - Database ID
        - Name
        - Status
        - Created date

        This is a read-only operation and will not modify the database.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `database_id`: The unique ID of the database
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'database_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $databaseId = $request->integer('database_id');

        try {
            $database = $client->databases()->get($serverId, $databaseId);

            return Response::text(json_encode([
                'success' => true,
                'database' => [
                    'id' => $database->id,
                    'name' => $database->name,
                    'status' => $database->status,
                    'created_at' => $database->createdAt,
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve database. Please verify the server_id and database_id are correct.',
            ], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()
                ->description('The unique ID of the Forge server')
                ->min(1)
                ->required(),
            'database_id' => $schema->integer()
                ->description('The unique ID of the database')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
