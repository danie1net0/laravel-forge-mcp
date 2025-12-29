<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Services;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\{Request, Response};

class InstallBlackfireTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Install Blackfire.io profiler on a Laravel Forge server.

        Blackfire helps you profile your PHP applications and find performance bottlenecks.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `server_id_token`: The Blackfire server ID
        - `server_token`: The Blackfire server token
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'server_id_token' => ['required', 'string'],
            'server_token' => ['required', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $serverIdToken = $request->string('server_id_token')->value();
        $serverToken = $request->string('server_token')->value();

        try {
            $client->services()->installBlackfire($serverId, $serverIdToken, $serverToken);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Blackfire installed successfully.',
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
            'server_id_token' => $schema->string()
                ->description('The Blackfire server ID')
                ->required(),
            'server_token' => $schema->string()
                ->description('The Blackfire server token')
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
