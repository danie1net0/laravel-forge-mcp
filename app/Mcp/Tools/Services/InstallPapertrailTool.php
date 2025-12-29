<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Services;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\{Request, Response};

class InstallPapertrailTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Install Papertrail log management on a Laravel Forge server.

        Papertrail provides centralized logging for your server.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `host`: The Papertrail host endpoint (e.g., logs.papertrailapp.com:12345)
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'host' => ['required', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $host = $request->string('host')->value();

        try {
            $client->services()->installPapertrail($serverId, $host);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Papertrail installed successfully.',
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
            'host' => $schema->string()
                ->description('The Papertrail host endpoint')
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
