<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Monitors;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Monitors\CreateMonitorData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateMonitorTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
    Create a new server monitor on a Laravel Forge server.

    **Required Parameters:**
    - `server_id`: The unique ID of the Forge server
    - `type`: Monitor type (e.g., "cpu", "disk", "memory")

    Additional parameters may be required depending on monitor type.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $data = $request->except('server_id');

        try {
            $createData = CreateMonitorData::from($data);
            $monitor = $client->monitors()->create($serverId, $createData);

            return Response::text(json_encode([
                'success' => true,
                'monitor' => [
                    'id' => $monitor->id,
                    'type' => $monitor->type,
                    'status' => $monitor->status,
                ],
                'message' => 'Monitor created successfully',
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
            'type' => $schema->string()->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
