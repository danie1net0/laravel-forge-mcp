<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Services;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

#[IsDestructive]
class StopServiceTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Stop a service on a Laravel Forge server.

        **WARNING**: Stopping critical services may cause downtime.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `service`: The service name to stop

        Common services: mysql, nginx, postgres, php8.4, php8.3, php8.2, redis, memcached
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'service' => ['required', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $service = $request->string('service')->value();

        try {
            $client->services()->stopService($serverId, $service);

            return Response::text(json_encode([
                'success' => true,
                'message' => "Service '{$service}' stopped successfully.",
                'server_id' => $serverId,
                'service' => $service,
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
            'service' => $schema->string()
                ->description('The service name to stop (e.g., mysql, nginx, php8.4)')
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
