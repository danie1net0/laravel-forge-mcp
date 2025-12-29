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
        - `workers`: Number of workers. Default: auto
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'server' => ['nullable', 'string', 'in:swoole,roadrunner,frankenphp'],
            'workers' => ['nullable'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $server = $request->string('server', 'swoole')->value();
        $workers = $request->has('workers') ? $request->string('workers')->value() : 'auto';

        try {
            $client->integrations()->enableOctane($serverId, $siteId, $server, $workers);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Octane enabled successfully.',
                'server_id' => $serverId,
                'site_id' => $siteId,
                'octane_server' => $server,
                'workers' => $workers,
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
            'server' => $schema->string()
                ->description('The Octane server to use (swoole, roadrunner, frankenphp)')
                ->enum(['swoole', 'roadrunner', 'frankenphp']),
            'workers' => $schema->string()
                ->description('Number of workers (or "auto")'),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
