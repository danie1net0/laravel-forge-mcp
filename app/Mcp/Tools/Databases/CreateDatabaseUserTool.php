<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Databases;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Databases\CreateDatabaseUserData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateDatabaseUserTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Create a new database user on a Laravel Forge server.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `name`: The username for the database user
        - `password`: The password for the database user (min 8 characters)

        **Optional Parameters:**
        - `databases`: Array of database IDs to grant access to

        **Warning:** This operation will create a new database user on the server.
        Make sure to use a strong password and store it securely.

        Returns the created database user information including ID, name, and status.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'databases' => ['nullable', 'array'],
            'databases.*' => ['integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $data = [
            'name' => $request->string('name'),
            'password' => $request->string('password'),
        ];

        if ($request->has('databases')) {
            $data['databases'] = $request->array('databases');
        }

        try {
            $createData = CreateDatabaseUserData::from($data);
            $user = $client->databaseUsers()->create($serverId, $createData);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Database user created successfully',
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
                'message' => 'Failed to create database user. Please check the parameters and try again.',
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
                ->description('The username for the database user')
                ->required(),
            'password' => $schema->string()
                ->description('The password for the database user (min 8 characters)')
                ->required(),
            'databases' => $schema->array()
                ->description('Array of database IDs to grant access to (optional)')
                ->items($schema->integer()->min(1)),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
