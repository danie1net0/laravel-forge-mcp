<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Daemons;

use App\Services\ForgeService;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Exception;

#[IsReadOnly]
class ListDaemonsTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all daemons (long-running processes) on a specific Laravel Forge server.

        Returns a list of daemons including:
        - Daemon ID
        - Command
        - User
        - Directory
        - Processes count
        - Status
        - Created date

        This is a read-only operation and will not modify any daemons.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
    MARKDOWN;

    public function handle(Request $request, ForgeService $forge): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $daemons = $forge->listDaemons($serverId);

            $formatted = array_map(fn ($daemon) => [
                'id' => $daemon->id,
                'command' => $daemon->command ?? null,
                'user' => $daemon->user ?? null,
                'directory' => $daemon->directory ?? null,
                'processes' => $daemon->processes ?? null,
                'startsecs' => $daemon->startsecs ?? null,
                'status' => $daemon->status ?? null,
                'created_at' => $daemon->createdAt ?? null,
            ], $daemons);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'count' => count($formatted),
                'daemons' => $formatted,
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
                ->minimum(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
