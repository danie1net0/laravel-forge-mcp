<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Deployments;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class GetDeploymentHistoryOutputTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get the complete output/log for a specific deployment from the deployment history.

        Returns the full deployment output including:
        - Git pull output
        - Composer install output
        - Build command outputs
        - Migration output (if configured)
        - All stdout and stderr
        - Any errors or warnings

        This is useful for:
        - Debugging deployment failures
        - Verifying deployment steps
        - Investigating build errors
        - Reviewing migration output

        This is a read-only operation and will not modify any data.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `deployment_id`: The unique ID of the deployment
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'deployment_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $deploymentId = $request->integer('deployment_id');

        try {
            $outputData = $client->sites()->deploymentHistoryOutput($serverId, $siteId, $deploymentId);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'deployment_id' => $deploymentId,
                'output' => $outputData,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve deployment output. The deployment may not exist or may not have output.',
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
            'deployment_id' => $schema->integer()
                ->description('The unique ID of the deployment')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
