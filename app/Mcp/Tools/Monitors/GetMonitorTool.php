<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Monitors;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class GetMonitorTool extends Tool
{
    protected string $description = 'Get detailed information about a specific server monitor.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'monitor_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $monitor = $client->monitors()->get(
                $request->integer('server_id'),
                $request->integer('monitor_id')
            );

            return Response::text(json_encode([
                'success' => true,
                'monitor' => [
                    'id' => $monitor->id,
                    'type' => $monitor->type,
                    'status' => $monitor->status,
                ],
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
            'server_id' => $schema->integer()->min(1)->required(),
            'monitor_id' => $schema->integer()->min(1)->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
