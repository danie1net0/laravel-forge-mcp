<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Firewall;

use App\Services\ForgeService;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Attributes\IsReadOnly;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Exception;

#[IsReadOnly]
class ListFirewallRulesTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all firewall rules on a specific Laravel Forge server.

        Returns a list of firewall rules including:
        - Rule ID
        - Name
        - Port
        - Type (allow/deny)
        - IP addresses
        - Status
        - Created date

        This is a read-only operation and will not modify any firewall rules.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
    MARKDOWN;

    public function handle(Request $request, ForgeService $forge): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $rules = $forge->listFirewallRules($serverId);

            $formatted = array_map(fn ($rule) => [
                'id' => $rule->id,
                'name' => $rule->name ?? null,
                'port' => $rule->port ?? null,
                'type' => $rule->type ?? null,
                'ip_address' => $rule->ipAddress ?? null,
                'status' => $rule->status ?? null,
                'created_at' => $rule->createdAt ?? null,
            ], $rules);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'count' => count($formatted),
                'rules' => $formatted,
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
                ->minimum(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
