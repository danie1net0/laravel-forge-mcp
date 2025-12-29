<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Databases;

use App\Integrations\Forge\Data\Databases\DatabaseData;
use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListDatabasesTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all databases on a specific Laravel Forge server.

        **Common Use Cases:**
        - Finding database ID before managing database users
        - Verifying a database was created successfully
        - Getting an overview of all databases on a server
        - Checking database status before connecting
        - Planning database maintenance or backups

        **What You'll Get:**
        Returns a list of databases including:
        - Database ID (needed for user management and operations)
        - Database name (e.g., "myapp_production")
        - Status (creating, created, failed)
        - Created date

        **When to Use:**
        - Before creating database users: find the database_id
        - After site creation: verify the database was auto-created
        - For Laravel apps: check if database matches DB_DATABASE in .env
        - Troubleshooting: verify database exists and is ready

        **Database Naming Best Practices:**
        - Use descriptive names: "myapp_production" not "db1"
        - Separate by environment: "myapp_staging", "myapp_production"
        - Avoid special characters and spaces
        - Keep names under 64 characters (MySQL limit)

        **Next Steps:**
        - Use `create-database-tool` to create a new database
        - Use `create-database-user-tool` to add users to a database
        - Use `list-database-users-tool` to see who has access
        - Use `get-database-tool` for detailed database information

        This is a read-only operation and will not modify any databases.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $databases = $client->databases()->list($serverId)->databases;

            $formatted = array_map(fn (DatabaseData $db): array => [
                'id' => $db->id,
                'name' => $db->name,
                'status' => $db->status,
                'created_at' => $db->createdAt,
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
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
