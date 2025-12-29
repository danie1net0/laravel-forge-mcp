<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Composite;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class BulkDeployTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Deploy multiple sites at once across one or more servers.

        **Required Parameters:**
        - `deployments`: Array of deployment targets, each containing:
          - `server_id`: The server ID
          - `site_id`: The site ID

        Returns deployment status for all sites including success/failure tracking.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'deployments' => ['required', 'array', 'min:1'],
            'deployments.*.server_id' => ['required', 'integer', 'min:1'],
            'deployments.*.site_id' => ['required', 'integer', 'min:1'],
        ]);

        $deployments = $request->collect('deployments')->toArray();

        $results = [
            'total' => count($deployments),
            'successful' => 0,
            'failed' => 0,
            'deployments' => [],
        ];

        foreach ($deployments as $deployment) {
            $serverId = (int) $deployment['server_id'];
            $siteId = (int) $deployment['site_id'];

            try {
                $site = $client->sites()->get($serverId, $siteId);

                $client->sites()->deploy($serverId, $siteId);

                $results['successful']++;
                $results['deployments'][] = [
                    'server_id' => $serverId,
                    'site_id' => $siteId,
                    'site_name' => $site->name,
                    'status' => 'triggered',
                    'message' => 'Deployment triggered successfully',
                ];
            } catch (Exception $e) {
                $results['failed']++;
                $results['deployments'][] = [
                    'server_id' => $serverId,
                    'site_id' => $siteId,
                    'site_name' => null,
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return Response::text(json_encode([
            'success' => $results['failed'] === 0,
            'summary' => [
                'total' => $results['total'],
                'successful' => $results['successful'],
                'failed' => $results['failed'],
            ],
            'deployments' => $results['deployments'],
        ], JSON_PRETTY_PRINT));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'deployments' => $schema->array()
                ->items($schema->object())
                ->description('Array of deployment targets. Each object must have server_id (integer) and site_id (integer). Minimum 1 item required.')
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
