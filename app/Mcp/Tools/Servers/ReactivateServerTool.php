<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Servers;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class ReactivateServerTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Reactivate a server that was marked for deletion.

        Use this to restore a server before the deletion grace period expires.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server

        The server will be restored to its previous state.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $client->servers()->reactivate($serverId);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Server reactivated successfully.',
                'server_id' => $serverId,
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
