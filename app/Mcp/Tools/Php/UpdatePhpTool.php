<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Php;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\{Request, Response};

class UpdatePhpTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Update a PHP version to the latest patch version on a Laravel Forge server.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `version`: The PHP version to update (e.g., php82, php83)
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'version' => ['required', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $version = $request->string('version')->value();

        try {
            $client->php()->update($serverId, $version);

            return Response::text(json_encode([
                'success' => true,
                'message' => "PHP {$version} update initiated.",
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
            'version' => $schema->string()
                ->description('The PHP version to update (e.g., php82, php83)')
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
