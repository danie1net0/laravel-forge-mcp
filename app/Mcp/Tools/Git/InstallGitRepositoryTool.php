<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Git;

use App\Integrations\Forge\Data\Sites\InstallGitRepositoryData;
use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class InstallGitRepositoryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
    Install a Git repository on a Laravel Forge site.

    This connects your site to a Git repository (GitHub, GitLab, Bitbucket, etc.) for deployments.

    **Required Parameters:**
    - `server_id`: The unique ID of the Forge server
    - `site_id`: The unique ID of the site
    - `provider`: Git provider (github, gitlab, bitbucket, custom)
    - `repository`: Repository in format `user/repo` or full URL for custom

    **Optional Parameters:**
    - `branch`: Git branch to deploy (default: main/master)
    - `composer`: Run composer install (default: false)

    **Example:**
    ```json
    {
        "server_id": 1,
        "site_id": 1,
        "provider": "github",
        "repository": "username/repo-name",
        "branch": "main",
        "composer": true
    }
    ```

    The site will be configured to deploy from this repository.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'provider' => ['required', 'string'],
            'repository' => ['required', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $data = InstallGitRepositoryData::from($request->except(['server_id', 'site_id']));

        try {
            $site = $client->sites()->installGitRepository($serverId, $siteId, $data);

            return Response::text(json_encode([
                'success' => true,
                'site' => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'repository' => $site->repository,
                    'repository_branch' => $site->repositoryBranch,
                    'repository_status' => $site->repositoryStatus,
                ],
                'message' => 'Git repository installed successfully',
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
            'server_id' => $schema->integer()->min(1)->required(),
            'site_id' => $schema->integer()->min(1)->required(),
            'provider' => $schema->string()->required(),
            'repository' => $schema->string()->required(),
            'branch' => $schema->string(),
            'composer' => $schema->boolean(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
