<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Workers;

use App\Integrations\Forge\Data\Workers\CreateWorkerData;
use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateWorkerTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
    Create a new queue worker on a Laravel Forge site.

    Queue workers process background jobs from queues (Redis, Database, SQS, etc).
    This tool creates a new worker process that will continuously process jobs from
    the specified queue(s).

    **WARNING**: This is a destructive operation that creates new infrastructure on your server.

    **Required Parameters:**
    - `server_id`: The unique ID of the Forge server
    - `site_id`: The unique ID of the site
    - `connection`: Queue connection name (e.g., "redis", "database", "sqs")
    - `queue`: Queue name(s) to process (e.g., "default" or "high,default,low")

    **Optional Parameters:**
    - `timeout`: Maximum seconds a job can run (default: 60)
    - `sleep`: Seconds to sleep when no jobs available (default: 3)
    - `tries`: Number of times to attempt a job (default: 1)
    - `daemon`: Run as daemon (default: true)
    - `force`: Force worker to run even if in maintenance mode (default: false)

    **Examples:**
    - Basic Redis worker: `{"server_id": 1, "site_id": 1, "connection": "redis", "queue": "default"}`
    - Multiple queues: `{"server_id": 1, "site_id": 1, "connection": "redis", "queue": "high,default,low"}`
    - Custom timeout: `{"server_id": 1, "site_id": 1, "connection": "redis", "queue": "default", "timeout": 300}`

    The worker will start automatically after creation and will continuously process jobs.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'connection' => ['required', 'string'],
            'queue' => ['required', 'string'],
            'timeout' => ['sometimes', 'integer', 'min:1'],
            'sleep' => ['sometimes', 'integer', 'min:0'],
            'tries' => ['sometimes', 'integer', 'min:1'],
            'daemon' => ['sometimes', 'boolean'],
            'force' => ['sometimes', 'boolean'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');

        $data = [
            'connection' => $request->string('connection'),
            'queue' => $request->string('queue'),
        ];

        if ($request->has('timeout')) {
            $data['timeout'] = $request->integer('timeout');
        }

        if ($request->has('sleep')) {
            $data['sleep'] = $request->integer('sleep');
        }

        if ($request->has('tries')) {
            $data['tries'] = $request->integer('tries');
        }

        if ($request->has('daemon')) {
            $data['daemon'] = $request->boolean('daemon');
        }

        if ($request->has('force')) {
            $data['force'] = $request->boolean('force');
        }

        try {
            $createData = CreateWorkerData::from($data);
            $worker = $client->workers()->create($serverId, $siteId, $createData);

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
                    'daemon' => (bool) $worker->daemon,
                    'status' => $worker->status,
                    'created_at' => $worker->createdAt,
                ],
                'message' => "Worker created successfully and is {$worker->status}",
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to create worker. Please verify the configuration is correct.',
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
            'connection' => $schema->string()
                ->description('Queue connection name (e.g., "redis", "database", "sqs")')
                ->required(),
            'queue' => $schema->string()
                ->description('Queue name(s) to process (e.g., "default" or "high,default,low")')
                ->required(),
            'timeout' => $schema->integer()
                ->description('Optional: Maximum seconds a job can run (default: 60)')
                ->min(1),
            'sleep' => $schema->integer()
                ->description('Optional: Seconds to sleep when no jobs available (default: 3)')
                ->min(0),
            'tries' => $schema->integer()
                ->description('Optional: Number of times to attempt a job (default: 1)')
                ->min(1),
            'daemon' => $schema->boolean()
                ->description('Optional: Run as daemon (default: true)'),
            'force' => $schema->boolean()
                ->description('Optional: Force worker to run even if in maintenance mode (default: false)'),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
