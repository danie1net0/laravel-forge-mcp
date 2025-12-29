<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Databases;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Databases\CreateDatabaseData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateDatabaseTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Create a new database on a Laravel Forge server.

        **Common Use Cases:**
        - Setting up database for a new Laravel application
        - Creating separate databases for staging/production environments
        - Adding a database for a new site or microservice
        - Creating test databases for development

        **What This Does:**
        - Creates a new MySQL/PostgreSQL database on the server
        - Optionally creates a database user with access
        - Sets appropriate permissions
        - Returns database credentials (save these!)

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `name`: The name of the database to create

        **Optional Parameters:**
        - `user`: Database username (defaults to "forge")
        - `password`: Database password (auto-generated if not provided, min 8 chars)

        **Examples:**

        Basic database (uses forge user):
        ```json
        {
            "server_id": 123,
            "name": "myapp_production"
        }
        ```

        Database with custom user:
        ```json
        {
            "server_id": 123,
            "name": "myapp_production",
            "user": "myapp_admin",
            "password": "securePassword123!"
        }
        ```

        Staging database:
        ```json
        {
            "server_id": 123,
            "name": "myapp_staging"
        }
        ```

        **Naming Best Practices:**
        - Use descriptive names: "myapp_production" not "db1"
        - Include environment: "_production", "_staging", "_test"
        - Use lowercase and underscores (avoid spaces and special chars)
        - Keep under 64 characters (MySQL limit)
        - Match your Laravel DB_DATABASE in .env

        **After Creating:**
        1. Save the returned database credentials securely
        2. Update .env file with database name and credentials
        3. Use `list-database-users-tool` to verify user was created
        4. Test connection from your application
        5. Run migrations: `php artisan migrate`

        **Security Notes:**
        - Auto-generated passwords are secure (20+ random characters)
        - If providing your own password, use 12+ characters with mixed types
        - Don't use the same password across environments
        - Store credentials in .env file, never commit to Git

        **Next Steps:**
        - Create database users: `create-database-user-tool`
        - List databases: `list-databases-tool`
        - Update Laravel .env with credentials
        - Run migrations on the new database

        **Warning:** This operation will create a new database on the server.
        Make sure the database name doesn't conflict with existing databases.

        Returns the created database information including ID, name, status, and credentials.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'user' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $serverId = $request->integer('server_id');
        $data = [
            'name' => $request->string('name'),
        ];

        if ($request->has('user')) {
            $data['user'] = $request->string('user');
        }

        if ($request->has('password')) {
            $data['password'] = $request->string('password');
        }

        try {
            $createData = CreateDatabaseData::from($data);
            $database = $client->databases()->create($serverId, $createData);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Database created successfully',
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
                'message' => 'Failed to create database. Please check the parameters and try again.',
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
            'name' => $schema->string()
                ->description('The name of the database to create')
                ->required(),
            'user' => $schema->string()
                ->description('Database username (optional, defaults to "forge")'),
            'password' => $schema->string()
                ->description('Database password (optional, auto-generated if not provided, min 8 characters)'),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
