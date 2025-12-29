<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Workers;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use App\Integrations\Forge\Data\Workers\WorkerData;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class ListWorkersTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
    List all queue workers for a specific site on a Laravel Forge server.

    Queue workers are processes that handle background jobs from queues (Redis, Database, etc).
    This endpoint returns information about all configured workers including their status,
    connection, queue names, and configuration.

    Returns a list of workers with:
    - Worker ID and status
    - Connection name (redis, database, sqs, etc)
    - Queue name
    - Timeout and sleep settings
    - Number of tries
    - Daemon mode status
    - Created timestamp

    This is useful for:
    - Monitoring queue worker health
    - Verifying worker configuration
    - Checking which queues are being processed
    - Identifying workers that need restart

    This is a read-only operation and will not modify any data.

    **Required Parameters:**
    - `server_id`: The unique ID of the Forge server
    - `site_id`: The unique ID of the site
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');

        try {
            $collection = $client->workers()->list($serverId, $siteId);
            $workers = $collection->workers;

            $formatted = array_map(fn (WorkerData $worker): array => [
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
            ], $workers);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'workers' => $formatted,
                'count' => count($formatted),
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve workers. Please verify the server_id and site_id are correct.',
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
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
