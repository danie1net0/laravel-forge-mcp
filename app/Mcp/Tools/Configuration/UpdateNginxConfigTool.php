<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Configuration;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\{Request, Response};

class UpdateNginxConfigTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Update the Nginx configuration for a site.

        **WARNING**: Incorrect configuration may make the site unavailable.
        The configuration will be validated before being applied.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `content`: The new Nginx configuration content
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'content' => ['required', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $content = $request->string('content')->value();

        try {
            $client->sites()->updateNginxConfig($serverId, $siteId, $content);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Nginx configuration updated successfully.',
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
            'content' => $schema->string()
                ->description('The new Nginx configuration content')
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
