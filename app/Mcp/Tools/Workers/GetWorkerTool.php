<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Workers;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class GetWorkerTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
    Get detailed information about a specific queue worker on a Laravel Forge server.

    Returns complete details about a worker including its current status, configuration,
    and execution parameters.

    Returns worker information including:
    - Worker ID and current status
    - Connection name (redis, database, sqs, etc)
    - Full command being executed
    - Queue name being processed
    - Timeout in seconds
    - Sleep seconds when no jobs available
    - Number of tries before failure
    - Environment (production, staging, etc)
    - Whether it's running as daemon
    - Creation timestamp

    This is useful for:
    - Debugging worker issues
    - Verifying worker configuration
    - Checking worker status before restart
    - Monitoring specific queue processing

    This is a read-only operation and will not modify any data.

    **Required Parameters:**
    - `server_id`: The unique ID of the Forge server
    - `site_id`: The unique ID of the site
    - `worker_id`: The unique ID of the worker
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'worker_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $workerId = $request->integer('worker_id');

        try {
            $worker = $client->workers()->get($serverId, $siteId, $workerId);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'worker' => [
                    'id' => $worker->id,
                    'connection' => $worker->connection,
                    'command' => $worker->command,
                    'queue' => $worker->queue,
                    'timeout' => $worker->timeout,
                    'sleep' => $worker->sleep,
                    'tries' => $worker->tries,
                    'environment' => $worker->environment,
                    'daemon' => (bool) $worker->daemon,
                    'status' => $worker->status,
                    'created_at' => $worker->createdAt,
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve worker information. Please verify the IDs are correct.',
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
            'site_id' => $schema->integer()
                ->description('The unique ID of the site')
                ->min(1)
                ->required(),
            'worker_id' => $schema->integer()
                ->description('The unique ID of the worker')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
