<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Deployments;

use App\Integrations\Forge\ForgeClient;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};
use Laravel\Mcp\Server\Tool;
use Exception;

#[IsReadOnly, IsIdempotent]
class GetDeploymentLogTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get the latest deployment log for a specific site.

        **Common Use Cases:**
        - Debugging why a deployment failed
        - Verifying a deployment completed successfully
        - Checking which migrations were run
        - Identifying dependency installation issues
        - Monitoring deployment performance

        **What You'll Get:**
        Returns the complete deployment log output from the last deployment, including:
        - Git pull output (which commit was deployed)
        - Composer install output (dependencies installed)
        - NPM/Yarn output (if frontend build configured)
        - Artisan migrate output (database changes)
        - Cache clearing commands
        - Any errors or warnings
        - Exit codes and status

        **How to Read the Logs:**
        - ✅ Success indicators: "Successfully deployed", exit code 0
        - ⚠️ Common errors to look for:
          - "composer install failed" → Check composer.json or memory limits
          - "npm install failed" → Check Node.js version or package.json
          - "Migration failed" → Check database connection or migration syntax
          - "Permission denied" → Check file permissions (775/664)
          - "fatal: could not read" → Check deploy key is installed

        **Best Practices:**
        - Always check logs immediately after deployment
        - Look for errors even if deployment "succeeded" (warnings matter)
        - Compare with previous successful deployments
        - Save important error messages for troubleshooting

        **Next Steps:**
        - If deployment failed: Fix the issue and use `deploy-site-tool` to redeploy
        - If errors found: Use `execute-site-command-tool` to run fix commands
        - For detailed history: Use `list-deployment-history-tool`
        - For script changes: Use `update-deployment-script-tool`

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
            $log = $client->sites()->deploymentLog($serverId, $siteId);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'log' => $log,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve deployment log. The site may not have been deployed yet.',
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
