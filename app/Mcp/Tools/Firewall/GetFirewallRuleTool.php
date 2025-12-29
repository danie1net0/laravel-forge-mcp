<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Firewall;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly]
#[IsIdempotent]
class GetFirewallRuleTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get detailed information about a specific firewall rule on a Laravel Forge server.

        Returns complete firewall rule information including:
        - Rule ID
        - Name
        - Port
        - Type (allow/deny)
        - IP addresses
        - Status
        - Created date

        This is a read-only operation and will not modify the firewall rule.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `rule_id`: The unique ID of the firewall rule
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'rule_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $ruleId = $request->integer('rule_id');

        try {
            $rule = $client->firewall()->get($serverId, $ruleId);

            return Response::text(json_encode([
                'success' => true,
                'firewall_rule' => [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'port' => $rule->port,
                    'ip_address' => $rule->ipAddress,
                    'status' => $rule->status,
                    'created_at' => $rule->createdAt,
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve firewall rule. Please verify the server_id and rule_id are correct.',
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
            'rule_id' => $schema->integer()
                ->description('The unique ID of the firewall rule')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
