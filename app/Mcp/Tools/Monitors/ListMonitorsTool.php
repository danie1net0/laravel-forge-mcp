<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Monitors;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use App\Integrations\Forge\Data\Monitors\MonitorData;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class ListMonitorsTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
    List all monitors configured on a Laravel Forge server.

    Monitors track server resources and can send alerts when thresholds are exceeded.

    **Required Parameters:**
    - `server_id`: The unique ID of the Forge server

    Returns monitor information including type and status.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $monitors = $client->monitors()->list($serverId)->monitors;

            $formatted = array_map(fn (MonitorData $monitor): array => [
                'id' => $monitor->id,
                'type' => $monitor->type,
                'status' => $monitor->status,
            ], $monitors);

            return Response::text(json_encode([
                'success' => true,
                'monitors' => $formatted,
                'count' => count($formatted),
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
