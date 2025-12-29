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
class ListDeploymentHistoryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List the complete deployment history for a specific site.

        Returns a chronological list of all deployments, including:
        - Deployment ID
        - Status (finished, failed, deploying)
        - Commit hash and branch
        - Committer information
        - Duration
        - Timestamps (started, finished)

        This is useful for:
        - Tracking deployment history
        - Identifying when issues were introduced
        - Monitoring deployment frequency
        - Auditing changes to production

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
            $history = $client->sites()->deploymentHistory($serverId, $siteId);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'deployments' => $history,
                'count' => count($history),
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve deployment history. The site may not have any deployments yet.',
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
