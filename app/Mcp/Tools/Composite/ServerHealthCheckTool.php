<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Composite;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class ServerHealthCheckTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Comprehensive server health check aggregating multiple metrics.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server

        Returns a complete health report including:
        - Server status and configuration
        - Running services status
        - Active monitors
        - Recent events
        - PHP and database information
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $server = $client->servers()->get($serverId);

            $monitors = [];

            try {
                $monitorsCollection = $client->monitors()->list($serverId);
                $monitors = $monitorsCollection->monitors;
            } catch (Exception) {
            }

            $events = [];

            try {
                $events = $client->servers()->listEvents($serverId);
            } catch (Exception) {
            }

            $sites = [];

            try {
                $sitesCollection = $client->sites()->list($serverId);
                $sites = $sitesCollection->sites;
            } catch (Exception) {
            }

            $daemons = [];

            try {
                $daemonsCollection = $client->daemons()->list($serverId);
                $daemons = $daemonsCollection->daemons;
            } catch (Exception) {
            }

            $workers = [];

            try {
                $workersCollection = $client->workers()->list($serverId, 0);
                $workers = $workersCollection->workers;
            } catch (Exception) {
            }

            $health = [
                'status' => 'healthy',
                'issues' => [],
                'warnings' => [],
            ];

            if (! $server->isReady) {
                $health['status'] = 'critical';
                $health['issues'][] = 'Server is not ready';
            }

            if (count($monitors) === 0) {
                $health['warnings'][] = 'No monitors configured';
            }

            $activeSites = count(array_filter($sites, fn ($site) => $site->status === 'installed'));

            if ($activeSites === 0 && count($sites) > 0) {
                $health['warnings'][] = 'Some sites are not fully installed';
            }

            if (count($health['issues']) > 0) {
                $health['status'] = 'critical';
            } elseif (count($health['warnings']) > 0) {
                $health['status'] = 'warning';
            }

            return Response::text(json_encode([
                'success' => true,
                'health_status' => $health['status'],
                'server' => [
                    'id' => $server->id,
                    'name' => $server->name,
                    'ip_address' => $server->ipAddress,
                    'provider' => $server->provider,
                    'region' => $server->region,
                    'size' => $server->size,
                    'php_version' => $server->phpVersion,
                    'database_type' => $server->databaseType,
                    'is_ready' => $server->isReady,
                ],
                'summary' => [
                    'total_sites' => count($sites),
                    'active_sites' => $activeSites,
                    'total_monitors' => count($monitors),
                    'total_daemons' => count($daemons),
                    'total_workers' => count($workers),
                    'recent_events' => count($events),
                ],
                'issues' => $health['issues'],
                'warnings' => $health['warnings'],
                'monitors' => array_map(fn ($m) => [
                    'id' => $m->id,
                    'type' => $m->type,
                    'status' => $m->status,
                ], array_slice($monitors, 0, 5)),
                'recent_events' => array_map(fn ($e) => [
                    'id' => $e['id'] ?? null,
                    'description' => $e['description'] ?? null,
                    'status' => $e['status'] ?? null,
                    'created_at' => $e['created_at'] ?? null,
                ], array_slice($events, 0, 5)),
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
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
