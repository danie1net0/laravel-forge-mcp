<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Databases;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class ListDatabaseUsersTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all database users on a Laravel Forge server.

        Returns a list of database users with their information including:
        - User ID
        - Username
        - Status (creating, created)
        - Associated databases
        - Created timestamp

        This is useful for:
        - Managing database access
        - Auditing database users
        - Planning user permissions
        - Monitoring user status

        This is a read-only operation and will not modify any databases or users.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $cursor = $request->has('cursor') ? $request->string('cursor')->value() : null;
        $pageSize = $request->has('page_size') ? $request->integer('page_size') : 30;

        try {
            $users = $client->databaseUsers()->list($serverId, $cursor, $pageSize)->users;

            return Response::text((string) json_encode([
                'success' => true,
                'server_id' => $serverId,
                'users' => $users,
                'count' => count($users),
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text((string) json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve database users. Please check if the server exists.',
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
            'cursor' => $schema->string()->description('Pagination cursor for next page')->nullable(),
            'page_size' => $schema->integer()->description('Items per page (default 30)')->min(1)->max(100)->nullable(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
