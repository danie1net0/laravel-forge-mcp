<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Sites;

use App\Services\ForgeService;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Forge\Resources\Site;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListSitesTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all sites on a specific Laravel Forge server.

        Returns a list of all sites configured on the server with their basic information including:
        - Site ID
        - Name/Domain
        - Directory path
        - Repository information (URL and branch)
        - Quick deploy status
        - Project type (Laravel, PHP, HTML, etc.)
        - PHP version
        - Status
        - Created date

        This is a read-only operation and will not modify any sites.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
    MARKDOWN;

    public function handle(Request $request, ForgeService $forge): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $sites = $forge->listSites($serverId);

            $formatted = array_map(fn (Site $site): array => [
                'id' => $site->id,
                'name' => $site->name,
                'directory' => $site->directory,
                'repository' => $site->repository ?? null,
                'repository_branch' => $site->repositoryBranch ?? null,
                'repository_status' => $site->repositoryStatus ?? null,
                'quick_deploy' => $site->quickDeploy ?? false,
                'project_type' => $site->projectType ?? null,
                'php_version' => $site->phpVersion ?? null,
                'is_secured' => $site->isSecured ?? false,
                'status' => $site->status ?? null,
                'created_at' => $site->createdAt ?? null,
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
