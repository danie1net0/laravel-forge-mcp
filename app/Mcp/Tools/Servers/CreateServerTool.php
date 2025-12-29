<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Servers;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Servers\CreateServerData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateServerTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Create a new server on Laravel Forge.

        ⚠️ **CRITICAL WARNING - FINANCIAL IMPACT** ⚠️

        This operation will:
        - Create a NEW server on your cloud provider (DigitalOcean, AWS, etc.)
        - **INCUR IMMEDIATE BILLING CHARGES** on your cloud provider account
        - Provision infrastructure that will continue charging until deleted
        - This action CANNOT be undone - you must manually delete the server

        **Required Parameters:**
        - `credential_id`: Your cloud provider credential ID from Forge
        - `name`: Server name (alphanumeric, dashes, underscores only)
        - `size`: Server size/plan from your provider (e.g., "s-1vcpu-1gb" for DO)
        - `region`: Provider region (e.g., "nyc3", "us-east-1")

        **Optional Parameters:**
        - `provider`: Cloud provider (default: "ocean" for DigitalOcean)
          - "ocean" (DigitalOcean), "aws", "linode", "hetzner", "vultr"
        - `php_version`: PHP version (default: "php82")
          - "php81", "php82", "php83"
        - `database`: Database type (default: null - no database)
          - "mysql8", "mysql57", "mariadb", "postgres15"
        - `database_name`: Database name (if database specified)
        - `load_balancer`: Create as load balancer (default: false)

        **Cost Examples (DigitalOcean):**
        - s-1vcpu-1gb: ~$6/month
        - s-2vcpu-2gb: ~$12/month
        - s-4vcpu-8gb: ~$48/month

        **Security Notes:**
        - Servers take 5-10 minutes to provision
        - Initial sudo/database passwords returned ONCE - save immediately
        - Firewall rules automatically configured for SSH/HTTP/HTTPS
        - SSL certificates can be added after creation

        Returns server details including initial passwords (store securely).
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'credential_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9-_]+$/'],
            'size' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'in:ocean,aws,linode,hetzner,vultr'],
            'php_version' => ['nullable', 'string', 'in:php81,php82,php83'],
            'database' => ['nullable', 'string', 'in:mysql8,mysql57,mariadb,postgres15'],
            'database_name' => ['nullable', 'string', 'max:255'],
            'load_balancer' => ['nullable', 'boolean'],
        ]);

        $data = [
            'credential_id' => $request->integer('credential_id'),
            'name' => $request->string('name'),
            'size' => $request->string('size'),
            'region' => $request->string('region'),
            'provider' => $request->string('provider', 'ocean'),
            'php_version' => $request->string('php_version', 'php82'),
        ];

        if ($request->has('database')) {
            $data['database'] = $request->string('database');

            if ($request->has('database_name')) {
                $data['database_name'] = $request->string('database_name');
            }
        }

        if ($request->has('load_balancer')) {
            $data['load_balancer'] = $request->boolean('load_balancer');
        }

        try {
            $createData = CreateServerData::from($data);
            $server = $client->servers()->create($createData);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Server creation started. This will take 5-10 minutes. SAVE THE PASSWORDS NOW - they will not be shown again.',
                'warning' => 'BILLING STARTED: Your cloud provider will now charge for this server until deleted.',
                'server' => [
                    'id' => $server->id,
                    'name' => $server->name,
                    'size' => $server->size,
                    'region' => $server->region,
                    'php_version' => $server->phpVersion,
                    'status' => 'provisioning',
                    'ip_address' => $server->ipAddress,
                    'sudo_password' => $server->sudoPassword ?? null,
                    'database_password' => $server->databasePassword ?? null,
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to create server. Check your credentials, region, and size parameters.',
            ], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'credential_id' => $schema->integer()
                ->description('Your cloud provider credential ID from Forge')
                ->min(1)
                ->required(),
            'name' => $schema->string()
                ->description('Server name (alphanumeric, dashes, underscores only)')
                ->required(),
            'size' => $schema->string()
                ->description('Server size/plan (e.g., "s-1vcpu-1gb" for DigitalOcean)')
                ->required(),
            'region' => $schema->string()
                ->description('Provider region (e.g., "nyc3", "us-east-1")')
                ->required(),
            'provider' => $schema->string()
                ->description('Cloud provider: ocean, aws, linode, hetzner, vultr (default: ocean)')
                ->enum(['ocean', 'aws', 'linode', 'hetzner', 'vultr']),
            'php_version' => $schema->string()
                ->description('PHP version: php81, php82, php83 (default: php82)')
                ->enum(['php81', 'php82', 'php83']),
            'database' => $schema->string()
                ->description('Database type: mysql8, mysql57, mariadb, postgres15')
                ->enum(['mysql8', 'mysql57', 'mariadb', 'postgres15']),
            'database_name' => $schema->string()
                ->description('Database name (required if database is specified)'),
            'load_balancer' => $schema->boolean()
                ->description('Create as load balancer (default: false)'),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
