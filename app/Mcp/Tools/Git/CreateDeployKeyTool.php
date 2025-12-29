<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Git;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateDeployKeyTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
    Create a new deploy key for a site.

    Deploy keys are SSH keys that allow Forge to pull code from private Git repositories.

    **Required Parameters:**
    - `server_id`: The unique ID of the Forge server
    - `site_id`: The unique ID of the site

    **Returns:**
    - The public SSH key that needs to be added to your Git provider

    After creating the deploy key, add the returned public key to your repository's
    deploy keys section (GitHub, GitLab, Bitbucket, etc.).
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
            $result = $client->sites()->createDeployKey($serverId, $siteId);

            return Response::text(json_encode([
                'success' => true,
                'deploy_key' => $result,
                'message' => 'Deploy key created successfully. Add this key to your Git repository.',
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
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
