<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Integrations;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\{Request, Response};

class EnableOctaneTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Enable Laravel Octane integration for a site.

        Octane supercharges your application using high-powered servers like Swoole or RoadRunner.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site

        **Optional Parameters:**
        - `server`: The Octane server to use (swoole, roadrunner, frankenphp). Default: swoole
        - `port`: The port for the Octane server. Default: 8000
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'server' => ['nullable', 'string', 'in:swoole,roadrunner,frankenphp'],
            'port' => ['nullable', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $server = $request->string('server', 'swoole')->value();
        $port = $request->has('port') ? $request->integer('port') : 8000;

        try {
            $client->integrations()->enableOctane($serverId, $siteId, $server, $port);

            return Response::text((string) json_encode([
                'success' => true,
                'message' => 'Octane enabled successfully.',
                'server_id' => $serverId,
                'site_id' => $siteId,
                'octane_server' => $server,
                'port' => $port,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text((string) json_encode([
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
            'server' => $schema->string()
                ->description('The Octane server to use (swoole, roadrunner, frankenphp)')
                ->enum(['swoole', 'roadrunner', 'frankenphp']),
            'port' => $schema->integer()
                ->description('The port for the Octane server (default: 8000)')
                ->min(1),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
