<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Databases;

use App\Integrations\Forge\Data\Databases\UpdateDatabaseUserData;
use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class UpdateDatabaseUserTool extends Tool
{
    protected string $description = 'Update database user permissions. Requires server_id, user_id, and databases array.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'user_id' => ['required', 'integer', 'min:1'],
            'databases' => ['required', 'array'],
        ]);

        try {
            $data = new UpdateDatabaseUserData(databases: $request->array('databases'));
            $user = $client->databaseUsers()->update($request->integer('server_id'), $request->integer('user_id'), $data);

            return Response::text(json_encode(['success' => true, 'message' => 'Database user updated'], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()->min(1)->required(),
            'user_id' => $schema->integer()->min(1)->required(),
            'databases' => $schema->array()->items($schema->integer())->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
