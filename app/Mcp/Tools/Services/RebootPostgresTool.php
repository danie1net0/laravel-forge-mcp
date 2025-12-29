<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Services;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\{Request, Response};

#[IsDestructive]
class RebootPostgresTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Reboot the PostgreSQL service on a Laravel Forge server.

        **WARNING**: This will temporarily disconnect all database connections.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $client->services()->rebootPostgres($serverId);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'PostgreSQL reboot initiated successfully.',
                'server_id' => $serverId,
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
