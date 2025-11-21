<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Sites;

use App\Services\ForgeService;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};
use Laravel\Mcp\Server\Tool;
use Exception;

#[IsReadOnly, IsIdempotent]
class GetSiteTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get detailed information about a specific site on a Laravel Forge server.

        Returns complete site information including:
        - Site ID, name, and directory
        - Repository details (URL, branch, provider, status)
        - Deployment settings (quick deploy, deployment script)
        - PHP version and configuration
        - SSL certificate status
        - Environment variables
        - Nginx configuration
        - Logs and status
        - Created/updated dates

        This is a read-only operation and will not modify the site.

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
            $site = $forge->getSite($serverId, $siteId);

            $siteData = [
                'id' => $site->id,
                'name' => $site->name,
                'directory' => $site->directory,
                'repository' => $site->repository ?? null,
                'repository_branch' => $site->repositoryBranch ?? null,
                'repository_provider' => $site->repositoryProvider ?? null,
                'repository_status' => $site->repositoryStatus ?? null,
                'quick_deploy' => $site->quickDeploy ?? false,
                'project_type' => $site->projectType ?? null,
                'php_version' => $site->phpVersion ?? null,
                'app' => $site->app ?? null,
                'app_status' => $site->appStatus ?? null,
                'is_secured' => $site->isSecured ?? false,
                'status' => $site->status ?? null,
                'deployment_status' => $site->deploymentStatus ?? null,
                'tags' => $site->tags ?? [],
                'created_at' => $site->createdAt ?? null,
            ];

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site' => $siteData,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve site information. Please verify the server_id and site_id are correct.',
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
