<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Deployments;

use App\Services\ForgeService;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

#[IsDestructive]
class DeploySiteTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Trigger a deployment for a specific site on a Laravel Forge server.

        This will execute the deployment script configured for the site, which typically includes:
        - Pulling latest code from git repository
        - Installing dependencies (Composer, npm)
        - Running migrations
        - Clearing and rebuilding caches
        - Restarting PHP-FPM

        **WARNING**: This is a destructive operation that will deploy new code to production.
        Make sure you have verified the changes before deploying.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site to deploy

        After triggering, use the `get-deployment-log-tool` to monitor deployment progress.
    MARKDOWN;

    public function handle(Request $request, ForgeService $forge): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');

        try {
            $forge->deploySite($serverId, $siteId);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Deployment triggered successfully.',
                'server_id' => $serverId,
                'site_id' => $siteId,
                'next_steps' => [
                    'Use get-deployment-log-tool to monitor deployment progress',
                    'Deployments typically take 1-5 minutes to complete',
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to trigger deployment. Verify the server_id and site_id are correct.',
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
            'site_id' => $schema->integer()
                ->description('The unique ID of the site to deploy')
                ->minimum(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
