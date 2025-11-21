<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Servers;

use App\Services\ForgeService;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Forge\Resources\Server;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListServersTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all servers in your Laravel Forge account.

        Returns a list of all servers with their basic information including:
        - Server ID
        - Name
        - IP address
        - Provider (DigitalOcean, Linode, AWS, etc.)
        - Region
        - Size/Plan
        - PHP version
        - Database type
        - Created date

        This is a read-only operation and will not modify any servers.
    MARKDOWN;

    public function handle(Request $request, ForgeService $forge): Response
    {
        try {
            $servers = $forge->listServers();

            $formatted = array_map(fn (Server $server): array => [
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
