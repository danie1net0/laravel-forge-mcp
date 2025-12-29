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
class GetDeploymentHistoryDeploymentTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get detailed information about a specific deployment from the deployment history.

        Returns complete deployment information including:
        - Deployment ID and status
        - Git commit information (hash, message, author)
        - Branch name
        - Deployment duration
        - Timestamps (started, finished)
        - Exit code and status

        This is useful for:
        - Investigating specific deployments
        - Comparing deployments
        - Debugging deployment issues
        - Tracking what was deployed when

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
            $deployment = $client->sites()->deploymentHistoryDeployment($serverId, $siteId, $deploymentId);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'deployment' => $deployment,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve deployment details. The deployment may not exist.',
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
