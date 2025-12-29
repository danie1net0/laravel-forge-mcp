<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Sites;

use App\Integrations\Forge\Data\Sites\SiteData;
use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListSitesTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all sites on a specific Laravel Forge server.

        **Common Use Cases:**
        - Finding a site ID before deploying or managing a site
        - Getting an overview of all applications on a server
        - Checking deployment status across all sites
        - Verifying repository connections

        **What You'll Get:**
        Returns a list of all sites configured on the server with their basic information including:
        - Site ID (needed for deployment and site management)
        - Name/Domain (e.g., "myapp.com")
        - Directory path (where your app lives)
        - Repository information (Git URL and branch)
        - Quick deploy status (auto-deploy enabled or disabled)
        - Project type (Laravel, PHP, static HTML, etc.)
        - PHP version
        - SSL status (is_secured)
        - Application status
        - Created date

        **When to Use:**
        - Before deploying: find the site_id you need
        - After creating a site: verify it was created successfully
        - Troubleshooting: check repository status and quick deploy settings
        - Monitoring: see which sites are secured with SSL

        **Next Steps After Listing Sites:**
        - Use `get-site-tool` for complete site details
        - Use `deploy-site-tool` to deploy a specific site
        - Use `get-deployment-log-tool` to check recent deployment
        - Use `create-site-tool` to add a new site

        This is a read-only operation and will not modify any sites.

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
            $sites = $client->sites()->list($serverId)->sites;

            $formatted = array_map(fn (SiteData $site): array => [
                'id' => $site->id,
                'name' => $site->name,
                'directory' => $site->directory,
                'repository' => $site->repository,
                'repository_branch' => $site->repositoryBranch,
                'repository_status' => $site->repositoryStatus,
                'quick_deploy' => $site->quickDeploy,
                'project_type' => $site->projectType,
                'php_version' => $site->phpVersion,
                'is_secured' => $site->isSecured,
                'status' => $site->status,
                'created_at' => $site->createdAt,
            ], $sites);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'count' => count($formatted),
                'sites' => $formatted,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve sites. Please verify the server_id is correct.',
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
