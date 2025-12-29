<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Servers;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteServerTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Delete a server from Laravel Forge.

        🚨 **CRITICAL WARNING - PERMANENT DESTRUCTION** 🚨

        This operation will:
        - **PERMANENTLY DELETE THE ENTIRE SERVER** from your cloud provider
        - **DESTROY ALL SITES** hosted on this server
        - **DELETE ALL DATABASES** on this server
        - **REMOVE ALL FILES** and configurations
        - **CANNOT BE UNDONE** - all data will be lost forever

        **Before deleting:**
        1. ✅ Backup all databases
        2. ✅ Download all important files
        3. ✅ Update DNS to point away from this server
        4. ✅ Verify no production sites are running
        5. ✅ Double-check this is the correct server

        **Required Parameters:**
        - `server_id`: The unique ID of the server to DELETE FOREVER

        **Financial Impact:**
        - Billing from your cloud provider will stop
        - Any reserved IPs may incur additional charges
        - Snapshots/backups may continue charging

        **What happens:**
        - Server removed from Forge immediately
        - Cloud provider destroys the server (usually within minutes)
        - All IP addresses released
        - All monitoring stops

        This is **IRREVERSIBLE**. All data will be **PERMANENTLY LOST**.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $client->servers()->delete($serverId);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Server deletion initiated. The server and ALL its data are being permanently destroyed.',
                'warning' => 'This action is IRREVERSIBLE. All sites, databases, and files on this server are now being deleted.',
                'server_id' => $serverId,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to delete server. Server may not exist or you may not have permission.',
            ], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()
                ->description('The unique ID of the server to DELETE PERMANENTLY')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
