<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Sites;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\{Request, Response};

class UpdateAliasesTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Update the domain aliases for a site.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `aliases`: Array of domain aliases
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'aliases' => ['required', 'array'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $aliases = $request->collect('aliases')->toArray();

        try {
            $client->sites()->updateAliases($serverId, $siteId, $aliases);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Aliases updated successfully.',
                'server_id' => $serverId,
                'site_id' => $siteId,
                'aliases' => $aliases,
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
            'aliases' => $schema->array()
                ->items($schema->string())
                ->description('Array of domain aliases')
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
