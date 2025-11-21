<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Servers;

use App\Services\ForgeService;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};
use Laravel\Mcp\Server\Tool;
use Exception;

#[IsReadOnly, IsIdempotent]
class GetServerTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get detailed information about a specific Laravel Forge server.

        Returns complete server information including:
        - Server ID, name, and credentials
        - IP addresses (public and private)
        - Provider and region details
        - Server size/plan
        - Installed software versions (PHP, MySQL, etc.)
        - Network configuration
        - Status information
        - Security settings
        - Backup configuration
        - Tags and notes

        This is a read-only operation and will not modify the server.

        **Required Parameters:**
        - `server_id`: The unique ID of the server in your Forge account
    MARKDOWN;

    public function handle(Request $request, ForgeService $forge): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $server = $forge->getServer($serverId);

            $serverData = [
                'id' => $server->id,
                'name' => $server->name,
                'ip_address' => $server->ipAddress,
                'private_ip_address' => $server->privateIpAddress,
                'provider' => $server->provider,
                'provider_id' => $server->providerId,
                'region' => $server->region,
                'size' => $server->size,
                'php_version' => $server->phpVersion,
                'php_cli_version' => $server->phpCliVersion,
                'database_type' => $server->databaseType,
                'opcache_status' => $server->opcacheStatus,
                'is_ready' => $server->isReady,
                'network' => $server->network ?? [],
                'tags' => $server->tags ?? [],
                'created_at' => $server->createdAt,
            ];

            return Response::text(json_encode([
                'success' => true,
                'server' => $serverData,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve server information. Please verify the server_id is correct.',
            ], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()
                ->description('The unique ID of the Forge server')
                ->minimum(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
