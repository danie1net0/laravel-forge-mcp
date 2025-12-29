<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Servers;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class GetEventOutputTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get the output of a specific server event.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `event_id`: The unique ID of the event

        Returns the console output from the event execution.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'event_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $eventId = $request->integer('event_id');

        try {
            $output = $client->servers()->getEventOutput($serverId, $eventId);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'event_id' => $eventId,
                'output' => $output,
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
            'event_id' => $schema->integer()
                ->description('The unique ID of the event')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
