<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Workers;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteWorkerTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
    Delete a queue worker from a Laravel Forge site.

    **WARNING**: This is a destructive operation that permanently removes the worker.

    This will stop the worker process and remove its configuration. Use this when:
    - Worker is no longer needed
    - Cleaning up old/unused workers
    - Reconfiguring queue processing strategy

    **IMPORTANT**: The worker will be immediately stopped and removed. Any jobs it was
    processing will be interrupted. Make sure to drain the queue or move jobs to other
    workers before deletion if needed.

    **Required Parameters:**
    - `server_id`: The unique ID of the Forge server
    - `site_id`: The unique ID of the site
    - `worker_id`: The unique ID of the worker to delete

    **Example:**
    ```json
    {
        "server_id": 1,
        "site_id": 1,
        "worker_id": 5
    }
    ```

    After deletion, jobs will no longer be processed from this worker's queue unless
    another worker is configured for that queue.
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
            $client->workers()->delete($serverId, $siteId, $workerId);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'worker_id' => $workerId,
                'message' => "Worker #{$workerId} deleted successfully",
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to delete worker. Please verify the worker exists and try again.',
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
                ->description('The unique ID of the worker to delete')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
