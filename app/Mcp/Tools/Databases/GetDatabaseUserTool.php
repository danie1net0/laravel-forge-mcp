<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Databases;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class GetDatabaseUserTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get detailed information about a specific database user on a Laravel Forge server.

        Returns complete database user information including:
        - User ID
        - Username
        - Status (creating, created)
        - List of databases the user has access to
        - Created timestamp

        This is useful for:
        - Verifying user permissions
        - Auditing database access
        - Troubleshooting connection issues
        - Managing user access

        This is a read-only operation and will not modify the user.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `user_id`: The unique ID of the database user
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'user_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $userId = $request->integer('user_id');

        try {
            $user = $client->databaseUsers()->get($serverId, $userId);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'status' => $user->status,
                    'created_at' => $user->createdAt,
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve database user. The user may not exist.',
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
            'user_id' => $schema->integer()
                ->description('The unique ID of the database user')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
