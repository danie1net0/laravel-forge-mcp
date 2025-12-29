<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Sites;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Sites\UpdateSiteData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class UpdateSiteTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Update site configuration on Laravel Forge.

        Allows updating site settings like directory, aliases, and isolation status.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site

        **Optional Parameters:**
        - `directory`: Web root directory (e.g., "/public" for Laravel)
        - `aliases`: Array of domain aliases
        - `isolated`: Enable PHP-FPM isolation (recommended for multi-tenant)

        **Warning:** Changing the directory will affect how the site serves files.
        Ensure the new directory exists and contains your application.

        Returns the updated site information.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'directory' => ['nullable', 'string', 'max:255'],
            'aliases' => ['nullable', 'array'],
            'aliases.*' => ['string'],
            'isolated' => ['nullable', 'boolean'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $data = [];

        if ($request->has('directory')) {
            $data['directory'] = $request->string('directory');
        }

        if ($request->has('aliases')) {
            $data['aliases'] = $request->array('aliases');
        }

        if ($request->has('isolated')) {
            $data['isolated'] = $request->boolean('isolated');
        }

        try {
            $updateData = UpdateSiteData::from($data);
            $site = $client->sites()->update($serverId, $siteId, $updateData);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Site updated successfully',
                'site' => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'directory' => $site->directory,
                    'aliases' => $site->aliases,
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to update site.',
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
            'directory' => $schema->string()
                ->description('Web root directory (e.g., "/public")'),
            'aliases' => $schema->array()
                ->description('Array of domain aliases')
                ->items($schema->string()),
            'isolated' => $schema->boolean()
                ->description('Enable PHP-FPM isolation'),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
