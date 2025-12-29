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
class RestartWorkerTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
    Restart a queue worker on a Laravel Forge site.

    This restarts the worker process, which is useful when:
    - Code has been deployed and workers need to reload
    - Configuration has changed
    - Worker is stuck or not processing jobs
    - Memory leaks need to be cleared

    **WARNING**: This will temporarily stop job processing on this worker during restart.

    The restart is graceful - current jobs will finish before the worker restarts.

    **Required Parameters:**
    - `server_id`: The unique ID of the Forge server
    - `site_id`: The unique ID of the site
    - `worker_id`: The unique ID of the worker to restart

    **Example:**
    ```json
    {
        "server_id": 1,
        "site_id": 1,
        "worker_id": 5
    }
    ```

    After restart, the worker will resume processing jobs from its configured queue(s).
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
            $client->workers()->restart($serverId, $siteId, $workerId);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'worker_id' => $workerId,
                'message' => "Worker #{$workerId} restarted successfully",
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to restart worker. Please verify the worker exists and try again.',
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
                ->description('The unique ID of the worker to restart')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
