<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Php;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\{Request, Response};

class InstallPhpTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Install a new PHP version on a Laravel Forge server.

        Available versions: php56, php70, php71, php72, php73, php74, php80, php81, php82, php83, php84

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `version`: The PHP version to install (e.g., php82, php83)
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
            $client->php()->install($serverId, $version);

            return Response::text(json_encode([
                'success' => true,
                'message' => "PHP {$version} installation initiated.",
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
                ->description('The PHP version to install (e.g., php82, php83)')
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
