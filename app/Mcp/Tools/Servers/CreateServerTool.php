<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Servers;

use App\Integrations\Forge\Data\Servers\CreateServerData;
use App\Integrations\Forge\ForgeClient;
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
        - Create a NEW server on your cloud provider
        - **INCUR IMMEDIATE BILLING CHARGES** on your cloud provider account
        - Provision infrastructure that will continue charging until deleted
        - This action CANNOT be undone - you must manually delete the server

        **Required Parameters:**
        - `name`: Server name (max 30 chars, alphanumeric, dashes, underscores)
        - `provider`: Cloud provider (ocean2, aws, hetzner, vultr, akamai, laravel, custom)
        - `type`: Server type (app, web, loadbalancer, database, cache, worker, meilisearch)
        - `ubuntu_version`: Ubuntu version (22.04, 24.04)

        **Provider-specific Parameters:**
        - `region_id`: Region for the provider (required for all except custom)
        - `size_id`: Size/plan for the provider (required for all except custom)
        - `ip_address`: Public IP (required for custom provider)
        - `private_ip_address`: Private IP (optional, custom provider only)

        **Optional Parameters:**
        - `credential_id`: Cloud provider credential ID from Forge
        - `team_id`: Team ID
        - `php_version`: PHP version (e.g., "php82", "php83", "php84")
        - `database_type`: Database type (e.g., "mysql8", "mariadb", "postgres16")
        - `recipe_id`: Recipe ID to run after provisioning
        - `tags`: Array of tags for the server
        - `add_key_to_source_control`: Add SSH key to source control (default: true)
        - `database`: Database name to create

        **Cost Examples (DigitalOcean):**
        - s-1vcpu-1gb: ~$6/month
        - s-2vcpu-2gb: ~$12/month
        - s-4vcpu-8gb: ~$48/month

        **Security Notes:**
        - Servers take 5-10 minutes to provision
        - Initial sudo/database passwords returned ONCE - save immediately
        - Firewall rules automatically configured for SSH/HTTP/HTTPS

        Returns server details including initial passwords (store securely).
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'name' => ['required', 'string', 'max:30', 'regex:/^[a-zA-Z0-9-_]+$/'],
            'provider' => ['required', 'string', 'in:ocean2,aws,hetzner,vultr,akamai,laravel,custom'],
            'type' => ['required', 'string', 'in:app,web,loadbalancer,database,cache,worker,meilisearch'],
            'ubuntu_version' => ['required', 'string', 'in:22.04,24.04'],
            'credential_id' => ['nullable', 'integer', 'min:1'],
            'team_id' => ['nullable', 'integer', 'min:1'],
            'php_version' => ['nullable', 'string', 'max:10'],
            'database_type' => ['nullable', 'string', 'max:50'],
            'recipe_id' => ['nullable', 'integer', 'min:1'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'add_key_to_source_control' => ['nullable', 'boolean'],
            'database' => ['nullable', 'string', 'max:255'],
            'region_id' => ['nullable', 'string', 'max:255'],
            'size_id' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'private_ip_address' => ['nullable', 'ip'],
        ]);

        $provider = (string) $request->string('provider');

        $data = [
            'name' => $request->string('name'),
            'provider' => $provider,
            'type' => $request->string('type'),
            'ubuntu_version' => $request->string('ubuntu_version'),
        ];

        $data[$provider] = $this->buildProviderConfig($request, $provider);

        $optionalFields = [
            'credential_id' => 'integer',
            'team_id' => 'integer',
            'php_version' => 'string',
            'database_type' => 'string',
            'recipe_id' => 'integer',
            'add_key_to_source_control' => 'boolean',
            'database' => 'string',
        ];

        foreach ($optionalFields as $field => $type) {
            if (! $request->has($field)) {
                continue;
            }

            $data[$field] = match ($type) {
                'integer' => $request->integer($field),
                'boolean' => $request->boolean($field),
                default => $request->string($field),
            };
        }

        if ($request->has('tags')) {
            $data['tags'] = $request->get('tags');
        }

        try {
            $createData = CreateServerData::from($data);
            $server = $client->servers()->create($createData);

            return Response::text((string) json_encode([
                'success' => true,
                'message' => 'Server creation started. This will take 5-10 minutes. SAVE THE PASSWORDS NOW - they will not be shown again.',
                'warning' => 'BILLING STARTED: Your cloud provider will now charge for this server until deleted.',
                'server' => [
                    'id' => $server->id,
                    'name' => $server->name,
                    'type' => $server->type,
                    'provider' => $server->provider,
                    'region' => $server->region,
                    'php_version' => $server->phpVersion,
                    'status' => 'provisioning',
                    'ip_address' => $server->ipAddress,
                    'sudo_password' => $server->sudoPassword ?? null,
                    'database_password' => $server->databasePassword ?? null,
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $exception) {
            return Response::text((string) json_encode([
                'success' => false,
                'error' => $exception->getMessage(),
                'message' => 'Failed to create server. Check your credentials, region, and size parameters.',
            ], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Server name (max 30 chars, alphanumeric, dashes, underscores)')
                ->required(),
            'provider' => $schema->string()
                ->description('Cloud provider: ocean2, aws, hetzner, vultr, akamai, laravel, custom')
                ->enum(['ocean2', 'aws', 'hetzner', 'vultr', 'akamai', 'laravel', 'custom'])
                ->required(),
            'type' => $schema->string()
                ->description('Server type: app, web, loadbalancer, database, cache, worker, meilisearch')
                ->enum(['app', 'web', 'loadbalancer', 'database', 'cache', 'worker', 'meilisearch'])
                ->required(),
            'ubuntu_version' => $schema->string()
                ->description('Ubuntu version: 22.04 or 24.04')
                ->enum(['22.04', '24.04'])
                ->required(),
            'region_id' => $schema->string()
                ->description('Region ID for the provider (required for all except custom)'),
            'size_id' => $schema->string()
                ->description('Size/plan ID for the provider (required for all except custom)'),
            'credential_id' => $schema->integer()
                ->description('Cloud provider credential ID from Forge')
                ->min(1),
            'team_id' => $schema->integer()
                ->description('Team ID')
                ->min(1),
            'php_version' => $schema->string()
                ->description('PHP version (e.g., "php82", "php83", "php84")'),
            'database_type' => $schema->string()
                ->description('Database type (e.g., "mysql8", "mariadb", "postgres16")'),
            'recipe_id' => $schema->integer()
                ->description('Recipe ID to run after provisioning')
                ->min(1),
            'tags' => $schema->array()
                ->description('Array of tags for the server'),
            'add_key_to_source_control' => $schema->boolean()
                ->description('Add SSH key to source control (default: true)'),
            'database' => $schema->string()
                ->description('Database name to create'),
            'ip_address' => $schema->string()
                ->description('Public IP address (required for custom provider)'),
            'private_ip_address' => $schema->string()
                ->description('Private IP address (custom provider only)'),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProviderConfig(Request $request, string $provider): array
    {
        if ($provider === 'custom') {
            return $this->buildCustomProviderConfig($request);
        }

        $config = [];

        if ($request->has('region_id')) {
            $config['region_id'] = $request->string('region_id');
        }

        if ($request->has('size_id')) {
            $config['size_id'] = $request->string('size_id');
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCustomProviderConfig(Request $request): array
    {
        $config = [];

        if ($request->has('ip_address')) {
            $config['ip_address'] = $request->string('ip_address');
        }

        if ($request->has('private_ip_address')) {
            $config['private_ip_address'] = $request->string('private_ip_address');
        }

        return $config;
    }
}
