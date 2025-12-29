<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Firewall;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Firewall\CreateFirewallRuleData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateFirewallRuleTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Create a new firewall rule on a Laravel Forge server.

        Firewall rules control which ports are accessible on your server and from which IP addresses.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `name`: A descriptive name for the firewall rule
        - `port`: The port number or range (e.g., "80", "443", "8000-9000")

        **Optional Parameters:**
        - `ip_address`: IP address to allow (defaults to "0.0.0.0/0" - all IPs). Use CIDR notation like "192.168.1.0/24" or specific IP like "203.0.113.1"

        **Common Ports:**
        - 80: HTTP
        - 443: HTTPS
        - 22: SSH (usually pre-configured)
        - 3306: MySQL
        - 5432: PostgreSQL
        - 6379: Redis

        **Security Warning:**
        - Opening ports to all IPs (0.0.0.0/0) can expose your server to security risks
        - Consider restricting access to specific IP addresses when possible
        - Avoid opening database ports (3306, 5432) to the public internet

        Returns the created firewall rule information.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'port' => ['required', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:255'],
        ]);

        $serverId = $request->integer('server_id');
        $data = [
            'name' => $request->string('name'),
            'port' => $request->string('port'),
        ];

        if ($request->has('ip_address')) {
            $data['ip_address'] = $request->string('ip_address');
        }

        try {
            $createData = CreateFirewallRuleData::from($data);
            $rule = $client->firewall()->create($serverId, $createData);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Firewall rule created successfully',
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
                'message' => 'Failed to create firewall rule. Please check the parameters and try again.',
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
            'name' => $schema->string()
                ->description('A descriptive name for the firewall rule')
                ->required(),
            'port' => $schema->string()
                ->description('The port number or range (e.g., "80", "443", "8000-9000")')
                ->required(),
            'ip_address' => $schema->string()
                ->description('IP address to allow (defaults to 0.0.0.0/0 - all IPs). Use CIDR notation or specific IP'),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
