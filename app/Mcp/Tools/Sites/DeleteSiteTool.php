<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Sites;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteSiteTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Delete a site from Laravel Forge.

        ⚠️ **WARNING - PERMANENT DELETION** ⚠️

        This operation will:
        - **PERMANENTLY DELETE the site** from Forge
        - **REMOVE all site files** from the server
        - **DELETE Nginx configuration**
        - **REMOVE SSL certificates** associated with this site
        - **STOP all workers/daemons** for this site
        - **CANNOT BE UNDONE**

        **Before deleting:**
        1. ✅ Backup all site files
        2. ✅ Backup the database (if any)
        3. ✅ Update DNS records
        4. ✅ Verify this is not a production site
        5. ✅ Check for dependent services

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site to DELETE

        **What is NOT deleted:**
        - Databases (must be deleted separately)
        - Scheduled jobs (must be deleted separately)
        - Environment variables are removed

        This is **IRREVERSIBLE**. Site files will be **PERMANENTLY LOST**.
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
            $client->sites()->delete($serverId, $siteId);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Site deleted permanently. All site files and configurations have been removed.',
                'warning' => 'This action is IRREVERSIBLE. The site and its files are now deleted.',
                'server_id' => $serverId,
                'site_id' => $siteId,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to delete site.',
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
                ->description('The unique ID of the site to DELETE PERMANENTLY')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
