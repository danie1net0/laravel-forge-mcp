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
class RevokeServerAccessTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Revoke Forge's SSH access to a server.

        **WARNING**: After revoking access, you will not be able to manage the server
        through Forge until you reconnect it.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server

        Use this when you want to prevent Forge from accessing the server.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $client->servers()->revokeAccess($serverId);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Server access revoked successfully.',
                'server_id' => $serverId,
                'warning' => 'Use reconnect-server-tool to restore access.',
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
