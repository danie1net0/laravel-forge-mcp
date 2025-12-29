<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Servers;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class ReconnectServerTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Reconnect Forge to a server after access was revoked.

        This generates a new SSH public key that must be added to the server's
        authorized_keys file manually.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server

        Returns the public key that needs to be added to the server.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $publicKey = $client->servers()->reconnect($serverId);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Reconnection initiated. Add the public key to the server.',
                'server_id' => $serverId,
                'public_key' => $publicKey,
                'instructions' => 'Add this key to /root/.ssh/authorized_keys on the server.',
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
