<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Sites;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\{Request, Response};

class ChangePhpVersionTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Change the PHP version for a site.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `version`: The PHP version (e.g., php82, php83)
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'version' => ['required', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $version = $request->string('version')->value();

        try {
            $client->sites()->changePhpVersion($serverId, $siteId, $version);

            return Response::text(json_encode([
                'success' => true,
                'message' => "PHP version changed to {$version}.",
                'server_id' => $serverId,
                'site_id' => $siteId,
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
            'site_id' => $schema->integer()
                ->description('The unique ID of the site')
                ->min(1)
                ->required(),
            'version' => $schema->string()
                ->description('The PHP version (e.g., php82, php83)')
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
