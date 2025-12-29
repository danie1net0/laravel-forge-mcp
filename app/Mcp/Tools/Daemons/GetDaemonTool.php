<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Daemons;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly]
#[IsIdempotent]
class GetDaemonTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get detailed information about a specific daemon (long-running process) on a Laravel Forge server.

        Returns complete daemon information including:
        - Daemon ID
        - Command
        - User
        - Directory
        - Processes count
        - Status
        - Created date

        This is a read-only operation and will not modify the daemon.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `daemon_id`: The unique ID of the daemon
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'daemon_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $daemonId = $request->integer('daemon_id');

        try {
            $daemon = $client->daemons()->get($serverId, $daemonId);

            return Response::text(json_encode([
                'success' => true,
                'daemon' => [
                    'id' => $daemon->id,
                    'command' => $daemon->command,
                    'user' => $daemon->user,
                    'directory' => $daemon->directory,
                    'status' => $daemon->status,
                    'created_at' => $daemon->createdAt,
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve daemon. Please verify the server_id and daemon_id are correct.',
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
            'daemon_id' => $schema->integer()
                ->description('The unique ID of the daemon')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
