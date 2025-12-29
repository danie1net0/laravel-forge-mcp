<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Php;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};
use Laravel\Mcp\{Request, Response};

#[IsReadOnly, IsIdempotent]
class ListPhpVersionsTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all PHP versions installed on a Laravel Forge server.

        Returns information about each PHP version including:
        - Version number
        - Status
        - Whether it's the default version
        - Whether it's used on CLI

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
            $versions = $client->php()->list($serverId);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'php_versions' => $versions,
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
