<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Deployments;

use App\Integrations\Forge\ForgeClient;
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

        **Common Use Cases:**
        - Deploy after pushing code to main/production branch
        - Manual deployment when quick deploy is disabled
        - Re-deploy after changing environment variables or configuration
        - Deploy after updating the deployment script
        - Force a deployment to clear caches or restart services

        **What Happens During Deployment:**
        The deployment script runs, which typically includes:
        1. Git pull latest code from configured branch
        2. Composer install (production dependencies)
        3. NPM install and build (if configured)
        4. Run database migrations (if in script)
        5. Clear and rebuild Laravel caches (config, route, view)
        6. Restart PHP-FPM to load new code
        7. Restart queue workers (if in script)

        **Before Deploying:**
        ✅ Verify tests are passing in CI/CD
        ✅ Check deployment script is correct: `get-deployment-script-tool`
        ✅ Review what will be deployed: check latest Git commits
        ✅ Consider enabling maintenance mode for large migrations
        ✅ Backup database if migrations will run
        ✅ Notify team if deploying to production

        **Deployment Timeline:**
        - Small apps (no migrations): 30 seconds - 1 minute
        - Medium apps (with migrations): 1-3 minutes
        - Large apps (heavy npm build): 3-5 minutes
        - If stuck >10 minutes: likely an error, check logs

        **After Deployment:**
        ✅ Monitor logs immediately: `get-deployment-log-tool`
        ✅ Verify site is accessible and functioning
        ✅ Check for errors in application logs
        ✅ Verify queue workers are running
        ✅ Test critical user flows

        **Common Issues:**
        - Deployment hangs: Check for prompts in script (use --no-interaction flags)
        - "Permission denied": File permissions issue (775 for dirs, 664 for files)
        - Composer timeout: Add --no-scripts or increase timeout
        - Migration failures: Check database connection and rollback plan

        **WARNING**: This is a destructive operation that will deploy new code to production.
        Make sure you have verified the changes before deploying.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site to deploy

        **Next Steps:**
        After triggering deployment, use `get-deployment-log-tool` to monitor progress.
        Typical workflow: deploy-site-tool → wait 2-3 seconds → get-deployment-log-tool
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
            $client->sites()->deploy($serverId, $siteId);

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
                ->min(1)
                ->required(),
            'site_id' => $schema->integer()
                ->description('The unique ID of the site to deploy')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
