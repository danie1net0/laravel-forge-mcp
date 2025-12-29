<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Servers;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

#[IsDestructive]
class UpdateDatabasePasswordTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Regenerate the database root password for a Laravel Forge server.

        **WARNING**: This is a destructive operation that will change the database password.
        Any applications using the old password will need to be updated.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server

        After the password is regenerated, you should update the .env file of any sites
        that connect to the database.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $client->servers()->updateDatabasePassword($serverId);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Database password regenerated successfully.',
                'server_id' => $serverId,
                'warning' => 'Update .env files for sites that use this database.',
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
