<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Deployments;

use App\Services\ForgeService;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};
use Laravel\Mcp\Server\Tool;
use Exception;

#[IsReadOnly, IsIdempotent]
class GetDeploymentScriptTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get the deployment script for a specific site.

        Returns the bash script that runs when a deployment is triggered. This typically includes:
        - Change to site directory
        - Git pull commands
        - Composer install
        - NPM/Yarn commands
        - Artisan commands (migrate, cache:clear, etc.)
        - Custom commands

        This is a read-only operation and will not modify any data.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
    MARKDOWN;

    public function handle(Request $request, ForgeService $forge): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');

        try {
            $script = $forge->getSiteDeploymentScript($serverId, $siteId);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'script' => $script,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve deployment script.',
            ], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()
                ->description('The unique ID of the Forge server')
                ->minimum(1)
                ->required(),
            'site_id' => $schema->integer()
                ->description('The unique ID of the site')
                ->minimum(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
