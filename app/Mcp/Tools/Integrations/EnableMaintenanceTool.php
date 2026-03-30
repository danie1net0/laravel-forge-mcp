<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Integrations;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\{Request, Response};

class EnableMaintenanceTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Enable Laravel maintenance mode for a site.

        Puts the Laravel application into maintenance mode, showing a maintenance page to visitors.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site

        **Optional Parameters:**
        - `secret`: A secret bypass key to access the site during maintenance
        - `status`: HTTP status code for the maintenance page (default: 503)
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'secret' => ['nullable', 'string'],
            'status' => ['nullable', 'integer', 'min:100', 'max:599'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $secret = $request->has('secret') ? $request->string('secret')->value() : null;
        $status = $request->has('status') ? $request->integer('status') : 503;

        try {
            $client->integrations()->enableMaintenance($serverId, $siteId, $secret, $status);

            return Response::text((string) json_encode([
                'success' => true,
                'message' => 'Maintenance mode enabled successfully.',
                'server_id' => $serverId,
                'site_id' => $siteId,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text((string) json_encode([
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
            'secret' => $schema->string()
                ->description('A secret bypass key to access the site during maintenance'),
            'status' => $schema->integer()
                ->description('HTTP status code for the maintenance page (default: 503)')
                ->min(100),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
