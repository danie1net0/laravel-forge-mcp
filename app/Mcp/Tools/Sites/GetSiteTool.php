<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Sites;

use App\Integrations\Forge\ForgeClient;
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

        **Common Use Cases:**
        - Verifying site configuration before deployment
        - Troubleshooting site issues (check repository status, PHP version, etc.)
        - Auditing site settings (SSL status, quick deploy, etc.)
        - Getting deployment URL or repository details
        - Checking if site is ready after creation

        **What You'll Get:**
        Returns complete site information including:
        - **Basic Info**: Site ID, domain name, directory path
        - **Repository**: Git URL, branch, provider (GitHub/GitLab/Bitbucket), sync status
        - **Deployment**: Quick deploy status, deployment status, app status
        - **PHP Configuration**: PHP version, project type (Laravel/static/etc.)
        - **Security**: SSL certificate status (is_secured), HTTPS enabled
        - **Application**: App type, app status, tags
        - **Timestamps**: Created and updated dates

        **When to Use:**
        - After creating a site: Verify it's configured correctly
        - Before deploying: Check repository is connected and status is good
        - Troubleshooting 500 errors: Check PHP version matches requirements
        - SSL issues: Verify is_secured is true
        - Repository sync issues: Check repository_status field

        **Key Fields to Check:**
        - `status`: "installed" means site is ready
        - `deployment_status`: Current deployment state
        - `repository_status`: "installed" means Git is connected
        - `is_secured`: true means SSL is active
        - `quick_deploy`: true means auto-deploy on push
        - `app_status`: Application health status

        **Troubleshooting Guide:**
        - `status` not "installed" → Site still provisioning, wait
        - `repository_status` null → Git not connected, use install-git-repository-tool
        - `is_secured` false → No SSL, use obtain-lets-encrypt-certificate-tool
        - `deployment_status` "failed" → Check deployment logs
        - Wrong `php_version` → Update site PHP version

        **Next Steps:**
        - Deploy the site: `deploy-site-tool`
        - Check deployment logs: `get-deployment-log-tool`
        - Update configuration: `update-site-tool`
        - Manage Git repository: `install-git-repository-tool` or `update-git-repository-tool`

        This is a read-only operation and will not modify the site.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');

        try {
            $site = $client->sites()->get($serverId, $siteId);

            $siteData = [
                'id' => $site->id,
                'name' => $site->name,
                'directory' => $site->directory,
                'repository' => $site->repository,
                'repository_branch' => $site->repositoryBranch,
                'repository_provider' => $site->repositoryProvider,
                'repository_status' => $site->repositoryStatus,
                'quick_deploy' => $site->quickDeploy,
                'project_type' => $site->projectType,
                'php_version' => $site->phpVersion,
                'app' => $site->app,
                'app_status' => $site->appStatus,
                'is_secured' => $site->isSecured,
                'status' => $site->status,
                'deployment_status' => $site->deploymentStatus,
                'tags' => $site->tags,
                'created_at' => $site->createdAt,
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
                ->min(1)
                ->required(),
            'site_id' => $schema->integer()
                ->description('The unique ID of the site')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
