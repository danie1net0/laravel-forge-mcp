<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Sites;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class UpdatePackagesAuthTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Update the Composer packages authentication configuration for a site.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `packages`: Object with package authentication configuration

        Example packages format:
        ```json
        {
          "github-oauth": {
            "github.com": "your-token"
          },
          "http-basic": {
            "repo.packagist.com": {
              "username": "user",
              "password": "password"
            }
          }
        }
        ```
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'packages' => ['required', 'array'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $packages = $request->collect('packages')->toArray();

        try {
            $client->sites()->updatePackagesAuth($serverId, $siteId, $packages);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Packages authentication updated successfully.',
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
            'packages' => $schema->object()
                ->description('Object with package authentication configuration')
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
