<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Servers;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

#[IsDestructive]
class PowerCycleServerTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Power cycle a Laravel Forge server (force power off and back on).

        **WARNING**: This is a destructive operation that performs a hard power cycle.
        It is equivalent to physically unplugging and replugging the server.
        Use this only when a normal reboot is not working, as it may cause data corruption.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server to power cycle
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $client->servers()->powerCycle($serverId);

            return Response::text((string) json_encode([
                'success' => true,
                'message' => 'Server power cycle initiated successfully.',
                'server_id' => $serverId,
                'warning' => 'The server will be unavailable while cycling. Use only when normal reboot fails.',
            ], JSON_PRETTY_PRINT));
        } catch (Exception $exception) {
            return Response::text((string) json_encode([
                'success' => false,
                'error' => $exception->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()
                ->description('The unique ID of the Forge server to power cycle')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
