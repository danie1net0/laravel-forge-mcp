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
class DestroyGitRepositoryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
    Remove Git repository integration from a site.

    **WARNING**: This disconnects the site from its Git repository. The site files
    will remain, but Git-based deployments will no longer work.

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
            $client->sites()->destroyGitRepository($serverId, $siteId);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Git repository removed from site successfully',
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
