<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Servers;

use App\Integrations\Forge\Data\Servers\ServerData;
use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListServersTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all servers in your Laravel Forge account.

        **Common Use Cases:**
        - Start of any server management workflow
        - Finding a server ID for other operations
        - Getting an overview of your infrastructure
        - Checking server status and readiness

        **What You'll Get:**
        Returns a list of all servers with their basic information including:
        - Server ID (needed for all server operations)
        - Name and IP address
        - Provider (DigitalOcean, Linode, AWS, etc.)
        - Region and size/plan
        - PHP version and database type
        - Ready status
        - Created date

        **When to Use:**
        - Before managing sites, use this to find the correct server_id
        - When you need to verify server configurations
        - To check if a new server has finished provisioning (is_ready field)

        **Next Steps:**
        - Use `list-sites-tool` to see sites on a specific server
        - Use `get-server-tool` for detailed information about a server
        - Use `create-server-tool` to provision a new server

        This is a read-only operation and will not modify any servers.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        try {
            $servers = $client->servers()->list()->servers;

            $formatted = array_map(fn (ServerData $server): array => [
                'id' => $server->id,
                'name' => $server->name,
                'type' => $server->type,
                'ip_address' => $server->ipAddress,
                'region' => $server->region,
                'size' => $server->size,
                'php_version' => $server->phpVersion,
                'is_ready' => $server->isReady,
                'created_at' => $server->createdAt,
            ], $servers);

            return Response::text(json_encode([
                'success' => true,
                'count' => count($formatted),
                'servers' => $formatted,
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
        return [];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
