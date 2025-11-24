<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Databases;

use App\Services\ForgeService;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class CreateDatabaseTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Create a new database on a Laravel Forge server.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `name`: The name of the database to create

        **Optional Parameters:**
        - `user`: Database username (defaults to "forge")
        - `password`: Database password (auto-generated if not provided)

        **Warning:** This operation will create a new database on the server.
        Make sure the database name doesn't conflict with existing databases.

        Returns the created database information including ID, name, and status.
    MARKDOWN;

    public function handle(Request $request, ForgeService $forge): Response
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
            $database = $forge->createDatabase($serverId, $data);

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
