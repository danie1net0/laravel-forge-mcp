<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Sites;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\{Request, Response};

class UpdateLoadBalancingTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Update the load balancing configuration for a site.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `servers`: Array of server IDs to balance load across

        **Optional Parameters:**
        - `method`: Load balancing method (round_robin, least_connections, ip_hash). Default: round_robin
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'servers' => ['required', 'array'],
            'method' => ['nullable', 'string', 'in:round_robin,least_connections,ip_hash'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $servers = $request->collect('servers')->toArray();
        $method = $request->string('method', 'round_robin')->value();

        try {
            $client->sites()->updateLoadBalancing($serverId, $siteId, $servers, $method);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Load balancing updated successfully.',
                'server_id' => $serverId,
                'site_id' => $siteId,
                'method' => $method,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
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
            'servers' => $schema->array()
                ->items($schema->integer())
                ->description('Array of server IDs to balance load across')
                ->required(),
            'method' => $schema->string()
                ->description('Load balancing method')
                ->enum(['round_robin', 'least_connections', 'ip_hash']),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
