<?php

declare(strict_types=1);

use App\Integrations\Forge\Data\User\UserData;
use App\Integrations\Forge\ForgeConnector;
use Saloon\Http\Faking\{MockClient, MockResponse};
use App\Integrations\Forge\Data\Jobs\{CreateJobData, JobCollectionData, JobData};
use App\Integrations\Forge\Resources\{BackupResource, CertificateResource, DaemonResource, DatabaseResource, DatabaseUserResource, FirewallResource, IntegrationResource, JobResource, MonitorResource, NginxTemplateResource, PhpResource, RedirectRuleResource, SSHKeyResource, SecurityRuleResource, ServerResource, ServiceResource, SiteResource, UserResource, WebhookResource, WorkerResource};
use App\Integrations\Forge\Data\Sites\{CreateSiteData, ExecuteSiteCommandData, InstallGitRepositoryData, SiteCollectionData, SiteData, UpdateGitRepositoryData, UpdateSiteData};
use App\Integrations\Forge\Data\Backups\{BackupConfigurationCollectionData, BackupConfigurationData, CreateBackupConfigurationData, UpdateBackupConfigurationData};
use App\Integrations\Forge\Data\Daemons\{CreateDaemonData, DaemonCollectionData, DaemonData};
use App\Integrations\Forge\Data\SSHKeys\{CreateSSHKeyData, SSHKeyCollectionData, SSHKeyData};
use App\Integrations\Forge\Data\Servers\{CreateServerData, ServerCollectionData, ServerData, UpdateServerData};
use App\Integrations\Forge\Data\Workers\{CreateWorkerData, WorkerCollectionData, WorkerData};
use App\Integrations\Forge\Data\Firewall\{CreateFirewallRuleData, FirewallRuleCollectionData, FirewallRuleData};
use App\Integrations\Forge\Data\Monitors\{CreateMonitorData, MonitorCollectionData, MonitorData};
use App\Integrations\Forge\Data\Webhooks\{CreateWebhookData, WebhookCollectionData, WebhookData};
use App\Integrations\Forge\Data\Databases\{CreateDatabaseData, CreateDatabaseUserData, DatabaseCollectionData, DatabaseData, DatabaseUserCollectionData, DatabaseUserData, UpdateDatabaseUserData};
use App\Integrations\Forge\Data\Certificates\{CertificateCollectionData, CertificateData, ObtainLetsEncryptCertificateData};
use App\Integrations\Forge\Data\RedirectRules\{CreateRedirectRuleData, RedirectRuleCollectionData, RedirectRuleData};
use App\Integrations\Forge\Data\SecurityRules\{CreateSecurityRuleData, SecurityRuleCollectionData, SecurityRuleData};
use App\Integrations\Forge\Data\NginxTemplates\{CreateNginxTemplateData, NginxTemplateCollectionData, NginxTemplateData, UpdateNginxTemplateData};

function serverMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'credential_id' => 1,
        'name' => 'test-server',
        'type' => 'app',
        'provider' => 'ocean2',
        'identifier' => 'x',
        'size' => '1GB',
        'region' => 'nyc1',
        'ubuntu_version' => '22.04',
        'db_status' => null,
        'redis_status' => null,
        'php_version' => 'php84',
        'php_cli_version' => 'php84',
        'opcache_status' => null,
        'database_type' => null,
        'ip_address' => '1.2.3.4',
        'ssh_port' => 22,
        'private_ip_address' => null,
        'local_public_key' => null,
        'blackfire_status' => null,
        'papertrail_status' => null,
        'revoked' => false,
        'created_at' => '2024-01-01T00:00:00Z',
        'is_ready' => true,
        'tags' => [],
        'network' => [],
    ], $overrides);
}

function siteMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'name' => 'test.com',
        'aliases' => null,
        'directory' => '/home/forge/test.com',
        'wildcards' => false,
        'status' => 'installed',
        'repository' => null,
        'repository_provider' => null,
        'repository_branch' => null,
        'repository_status' => null,
        'quick_deploy' => false,
        'deployment_status' => null,
        'project_type' => 'php',
        'app' => null,
        'app_status' => null,
        'hipchat_room' => null,
        'slack_channel' => null,
        'telegram_chat_id' => null,
        'telegram_chat_title' => null,
        'teams_webhook_url' => null,
        'discord_webhook_url' => null,
        'username' => 'forge',
        'balancing_status' => null,
        'created_at' => '2024-01-01T00:00:00Z',
        'deployment_url' => null,
        'is_secured' => false,
        'php_version' => 'php84',
        'tags' => [],
        'failure_deployment_emails' => null,
        'telegram_secret' => null,
        'web_directory' => '/public',
    ], $overrides);
}

function databaseMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'name' => 'forge',
        'status' => 'installed',
        'created_at' => '2024-01-01T00:00:00Z',
    ], $overrides);
}

function databaseUserMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'name' => 'forge',
        'status' => 'installed',
        'created_at' => '2024-01-01T00:00:00Z',
        'databases' => [1],
    ], $overrides);
}

function certificateMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'site_id' => 1,
        'domain' => 'test.com',
        'request_status' => null,
        'status' => 'installed',
        'type' => 'letsencrypt',
        'active' => true,
        'expires_at' => '2025-01-01T00:00:00Z',
        'created_at' => '2024-01-01T00:00:00Z',
        'activation_error' => null,
    ], $overrides);
}

function daemonMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'command' => 'php artisan queue:work',
        'user' => 'forge',
        'status' => 'installed',
        'directory' => '/home/forge/test.com',
        'created_at' => '2024-01-01T00:00:00Z',
    ], $overrides);
}

function firewallRuleMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'name' => 'Allow SSH',
        'port' => 22,
        'ip_address' => null,
        'status' => 'installed',
        'created_at' => '2024-01-01T00:00:00Z',
    ], $overrides);
}

function jobMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'command' => 'php artisan schedule:run',
        'user' => 'forge',
        'frequency' => 'minutely',
        'cron' => '* * * * *',
        'status' => 'installed',
        'created_at' => '2024-01-01T00:00:00Z',
    ], $overrides);
}

function monitorMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'status' => 'installed',
        'type' => 'disk',
        'operator' => 'gte',
        'threshold' => 80,
        'minutes' => 5,
        'state' => 'OK',
        'state_changed_at' => '2024-01-01T00:00:00Z',
    ], $overrides);
}

function workerMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'site_id' => 1,
        'connection' => 'redis',
        'command' => 'php artisan queue:work',
        'queue' => 'default',
        'timeout' => 60,
        'sleep' => 3,
        'tries' => 3,
        'environment' => 'production',
        'daemon' => 1,
        'status' => 'installed',
        'created_at' => '2024-01-01T00:00:00Z',
    ], $overrides);
}

function webhookMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'site_id' => 1,
        'url' => 'https://example.com/webhook',
        'created_at' => '2024-01-01T00:00:00Z',
    ], $overrides);
}

function sshKeyMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'name' => 'test-key',
        'status' => 'installed',
        'username' => 'forge',
        'created_at' => '2024-01-01T00:00:00Z',
    ], $overrides);
}

function redirectRuleMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'site_id' => 1,
        'from' => '/old',
        'to' => '/new',
        'type' => 'redirect',
        'status' => 'installed',
        'created_at' => '2024-01-01T00:00:00Z',
    ], $overrides);
}

function securityRuleMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'site_id' => 1,
        'name' => 'Admin Panel',
        'path' => '/admin',
        'credentials' => 'user:password',
        'created_at' => '2024-01-01T00:00:00Z',
    ], $overrides);
}

function backupConfigurationMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'day_of_week' => null,
        'time' => '00:00',
        'provider' => 's3',
        'provider_name' => 'Amazon S3',
        'last_backup_time' => null,
        'created_at' => '2024-01-01T00:00:00Z',
    ], $overrides);
}

function nginxTemplateMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'server_id' => 1,
        'name' => 'Default',
        'content' => 'server { listen 80; }',
    ], $overrides);
}

function userMockData(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'card_last_four' => null,
        'connected_to_github' => null,
        'connected_to_gitlab' => null,
        'connected_to_bitbucket' => null,
        'connected_to_bitbucket_two' => null,
        'connected_to_digitalocean' => null,
        'connected_to_linode' => null,
        'connected_to_vultr' => null,
        'connected_to_aws' => null,
        'connected_to_hetzner' => null,
        'ready_for_billing' => null,
        'stripe_is_active' => null,
        'can_create_servers' => true,
    ], $overrides);
}

/**
 * @return array{ForgeConnector, MockClient}
 */
function createMockedConnectorWithClient(array $responses): array
{
    $mockClient = new MockClient($responses);
    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    return [$connector, $mockClient];
}

function createMockedConnector(array $responses): ForgeConnector
{
    [$connector] = createMockedConnectorWithClient($responses);

    return $connector;
}

describe('ServerResource', function (): void {
    it('lists servers', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['servers' => [serverMockData()]]),
        ]);

        $resource = new ServerResource($connector);
        $result = $resource->list();

        expect($result)
            ->toBeInstanceOf(ServerCollectionData::class)
            ->servers->toHaveCount(1);
    });

    it('gets a server', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['server' => serverMockData()]),
        ]);

        $resource = new ServerResource($connector);
        $result = $resource->get(1);

        expect($result)
            ->toBeInstanceOf(ServerData::class)
            ->id->toBe(1)
            ->name->toBe('test-server')
            ->provider->toBe('ocean2');
    });

    it('creates a server', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['server' => serverMockData()]),
        ]);

        $resource = new ServerResource($connector);
        $data = CreateServerData::from([
            'credentialId' => 1,
            'name' => 'test-server',
            'size' => '1GB',
            'region' => 'nyc1',
        ]);
        $result = $resource->create($data);

        expect($result)
            ->toBeInstanceOf(ServerData::class)
            ->name->toBe('test-server');
    });

    it('updates a server', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['server' => serverMockData(['name' => 'updated-server'])]),
        ]);

        $resource = new ServerResource($connector);
        $data = UpdateServerData::from(['name' => 'updated-server']);
        $result = $resource->update(1, $data);

        expect($result)
            ->toBeInstanceOf(ServerData::class)
            ->name->toBe('updated-server');
    });

    it('deletes a server', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServerResource($connector);
        $resource->delete(1);

        $mockClient->assertSentCount(1);
    });

    it('reboots a server', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServerResource($connector);
        $resource->reboot(1);

        $mockClient->assertSentCount(1);
    });

    it('updates database password', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServerResource($connector);
        $resource->updateDatabasePassword(1);

        $mockClient->assertSentCount(1);
    });

    it('revokes access', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServerResource($connector);
        $resource->revokeAccess(1);

        $mockClient->assertSentCount(1);
    });

    it('reconnects a server and returns public key', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['public_key' => 'ssh-rsa AAAA...']),
        ]);

        $resource = new ServerResource($connector);
        $result = $resource->reconnect(1);

        expect($result)->toBe('ssh-rsa AAAA...');
    });

    it('reactivates a server', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServerResource($connector);
        $resource->reactivate(1);

        $mockClient->assertSentCount(1);
    });

    it('gets server log', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['content' => 'auth log content']),
        ]);

        $resource = new ServerResource($connector);
        $result = $resource->getLog(1);

        expect($result)->toBe('auth log content');
    });

    it('lists server events', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['events' => [['id' => 1, 'description' => 'deploy']]]),
        ]);

        $resource = new ServerResource($connector);
        $result = $resource->listEvents(1);

        expect($result)
            ->toBeArray()
            ->toHaveCount(1);
    });

    it('gets event output', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['output' => 'Event output text']),
        ]);

        $resource = new ServerResource($connector);
        $result = $resource->getEventOutput(1, 1);

        expect($result)->toBe('Event output text');
    });
});

describe('SiteResource', function (): void {
    it('lists sites', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['sites' => [siteMockData()]]),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->list(1);

        expect($result)
            ->toBeInstanceOf(SiteCollectionData::class)
            ->sites->toHaveCount(1);
    });

    it('gets a site', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['site' => siteMockData()]),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->get(1, 1);

        expect($result)
            ->toBeInstanceOf(SiteData::class)
            ->id->toBe(1)
            ->name->toBe('test.com');
    });

    it('creates a site', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['site' => siteMockData()]),
        ]);

        $resource = new SiteResource($connector);
        $data = CreateSiteData::from([
            'domain' => 'test.com',
            'projectType' => 'php',
        ]);
        $result = $resource->create(1, $data);

        expect($result)
            ->toBeInstanceOf(SiteData::class)
            ->name->toBe('test.com');
    });

    it('updates a site', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['site' => siteMockData(['directory' => '/home/forge/updated'])]),
        ]);

        $resource = new SiteResource($connector);
        $data = UpdateSiteData::from(['directory' => '/home/forge/updated']);
        $result = $resource->update(1, 1, $data);

        expect($result)
            ->toBeInstanceOf(SiteData::class)
            ->directory->toBe('/home/forge/updated');
    });

    it('deletes a site', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->delete(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('deploys a site', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->deploy(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('gets deployment script', function (): void {
        $connector = createMockedConnector([
            MockResponse::make('cd /home/forge/test.com && git pull'),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->deploymentScript(1, 1);

        expect($result)->toBe('cd /home/forge/test.com && git pull');
    });

    it('updates deployment script', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->updateDeploymentScript(1, 1, 'new script content');

        $mockClient->assertSentCount(1);
    });

    it('enables quick deploy', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->enableQuickDeploy(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('disables quick deploy', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->disableQuickDeploy(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('gets deployment log', function (): void {
        $connector = createMockedConnector([
            MockResponse::make('Deployment log content'),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->deploymentLog(1, 1);

        expect($result)->toBe('Deployment log content');
    });

    it('returns null for empty deployment log', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(''),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->deploymentLog(1, 1);

        expect($result)->toBeNull();
    });

    it('gets site log', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['log' => 'site log content']),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->log(1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveKey('log', 'site log content');
    });

    it('gets deployment history', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['deployments' => [['id' => 1, 'status' => 'finished']]]),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->deploymentHistory(1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveCount(1);
    });

    it('gets deployment history deployment', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['deployment' => ['id' => 1, 'status' => 'finished']]),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->deploymentHistoryDeployment(1, 1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveKey('status', 'finished');
    });

    it('gets deployment history output', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['output' => 'deployment output']),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->deploymentHistoryOutput(1, 1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveKey('output', 'deployment output');
    });

    it('gets command history', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['commands' => [['id' => 1, 'command' => 'php artisan migrate']]]),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->commandHistory(1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveCount(1);
    });

    it('gets a command', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['command' => ['id' => 1, 'command' => 'php artisan migrate']]),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->getCommand(1, 1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveKey('command', 'php artisan migrate');
    });

    it('executes a command', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['command' => ['id' => 1, 'command' => 'php artisan migrate', 'status' => 'running']]),
        ]);

        $resource = new SiteResource($connector);
        $data = ExecuteSiteCommandData::from(['command' => 'php artisan migrate']);
        $result = $resource->executeCommand(1, 1, $data);

        expect($result)
            ->toBeArray()
            ->toHaveKey('command', 'php artisan migrate');
    });

    it('installs git repository', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['site' => siteMockData(['repository' => 'user/repo', 'repository_provider' => 'github'])]),
        ]);

        $resource = new SiteResource($connector);
        $data = InstallGitRepositoryData::from([
            'provider' => 'github',
            'repository' => 'user/repo',
        ]);
        $result = $resource->installGitRepository(1, 1, $data);

        expect($result)
            ->toBeInstanceOf(SiteData::class)
            ->repository->toBe('user/repo');
    });

    it('updates git repository', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $data = UpdateGitRepositoryData::from(['branch' => 'main']);
        $resource->updateGitRepository(1, 1, $data);

        $mockClient->assertSentCount(1);
    });

    it('destroys git repository', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->destroyGitRepository(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('creates deploy key', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['key' => 'ssh-rsa AAAA...']),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->createDeployKey(1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveKey('key', 'ssh-rsa AAAA...');
    });

    it('deletes deploy key', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->deleteDeployKey(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('changes php version', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->changePhpVersion(1, 1, 'php84');

        $mockClient->assertSentCount(1);
    });

    it('clears site log', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->clearLog(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('gets nginx config', function (): void {
        $connector = createMockedConnector([
            MockResponse::make('server { listen 80; }'),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->getNginxConfig(1, 1);

        expect($result)->toBe('server { listen 80; }');
    });

    it('updates nginx config', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->updateNginxConfig(1, 1, 'server { listen 443; }');

        $mockClient->assertSentCount(1);
    });

    it('gets env file', function (): void {
        $connector = createMockedConnector([
            MockResponse::make('APP_NAME=Forge'),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->getEnvFile(1, 1);

        expect($result)->toBe('APP_NAME=Forge');
    });

    it('updates env file', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->updateEnvFile(1, 1, 'APP_NAME=Updated');

        $mockClient->assertSentCount(1);
    });

    it('lists aliases', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['aliases' => ['alias1.com', 'alias2.com']]),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->listAliases(1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveCount(2)
            ->toBe(['alias1.com', 'alias2.com']);
    });

    it('updates aliases', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->updateAliases(1, 1, ['alias1.com', 'alias2.com']);

        $mockClient->assertSentCount(1);
    });

    it('gets load balancing', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['method' => 'round_robin', 'servers' => []]),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->getLoadBalancing(1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveKey('method', 'round_robin');
    });

    it('updates load balancing', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->updateLoadBalancing(1, 1, [['id' => 2, 'weight' => 50]], 'round_robin');

        $mockClient->assertSentCount(1);
    });

    it('installs WordPress', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->installWordPress(1, 1, 'forge_wp', 'wp_admin');

        $mockClient->assertSentCount(1);
    });

    it('uninstalls WordPress', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->uninstallWordPress(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('installs phpMyAdmin', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->installPhpMyAdmin(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('uninstalls phpMyAdmin', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->uninstallPhpMyAdmin(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('resets deployment state', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->resetDeploymentState(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('sets deployment failure emails', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->setDeploymentFailureEmails(1, 1, ['admin@test.com']);

        $mockClient->assertSentCount(1);
    });

    it('gets packages auth', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['packages' => [['type' => 'composer', 'url' => 'https://repo.example.com']]]),
        ]);

        $resource = new SiteResource($connector);
        $result = $resource->getPackagesAuth(1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveCount(1);
    });

    it('updates packages auth', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SiteResource($connector);
        $resource->updatePackagesAuth(1, 1, [['type' => 'composer', 'url' => 'https://repo.example.com']]);

        $mockClient->assertSentCount(1);
    });
});

describe('DatabaseResource', function (): void {
    it('lists databases', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['databases' => [databaseMockData()]]),
        ]);

        $resource = new DatabaseResource($connector);
        $result = $resource->list(1);

        expect($result)
            ->toBeInstanceOf(DatabaseCollectionData::class)
            ->databases->toHaveCount(1);
    });

    it('gets a database', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['database' => databaseMockData()]),
        ]);

        $resource = new DatabaseResource($connector);
        $result = $resource->get(1, 1);

        expect($result)
            ->toBeInstanceOf(DatabaseData::class)
            ->id->toBe(1)
            ->name->toBe('forge');
    });

    it('creates a database', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['database' => databaseMockData(['name' => 'new_db'])]),
        ]);

        $resource = new DatabaseResource($connector);
        $data = CreateDatabaseData::from(['name' => 'new_db']);
        $result = $resource->create(1, $data);

        expect($result)
            ->toBeInstanceOf(DatabaseData::class)
            ->name->toBe('new_db');
    });

    it('deletes a database', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new DatabaseResource($connector);
        $resource->delete(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('syncs databases', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new DatabaseResource($connector);
        $resource->sync(1);

        $mockClient->assertSentCount(1);
    });
});

describe('DatabaseUserResource', function (): void {
    it('lists database users', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['users' => [databaseUserMockData()]]),
        ]);

        $resource = new DatabaseUserResource($connector);
        $result = $resource->list(1);

        expect($result)
            ->toBeInstanceOf(DatabaseUserCollectionData::class)
            ->users->toHaveCount(1);
    });

    it('gets a database user', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['user' => databaseUserMockData()]),
        ]);

        $resource = new DatabaseUserResource($connector);
        $result = $resource->get(1, 1);

        expect($result)
            ->toBeInstanceOf(DatabaseUserData::class)
            ->id->toBe(1)
            ->name->toBe('forge');
    });

    it('creates a database user', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['user' => databaseUserMockData(['name' => 'new_user'])]),
        ]);

        $resource = new DatabaseUserResource($connector);
        $data = CreateDatabaseUserData::from([
            'name' => 'new_user',
            'password' => 'secret123',
            'databases' => [1],
        ]);
        $result = $resource->create(1, $data);

        expect($result)
            ->toBeInstanceOf(DatabaseUserData::class)
            ->name->toBe('new_user');
    });

    it('updates a database user', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['user' => databaseUserMockData(['databases' => [1, 2]])]),
        ]);

        $resource = new DatabaseUserResource($connector);
        $data = UpdateDatabaseUserData::from(['databases' => [1, 2]]);
        $result = $resource->update(1, 1, $data);

        expect($result)
            ->toBeInstanceOf(DatabaseUserData::class)
            ->databases->toBe([1, 2]);
    });

    it('deletes a database user', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new DatabaseUserResource($connector);
        $resource->delete(1, 1);

        $mockClient->assertSentCount(1);
    });
});

describe('CertificateResource', function (): void {
    it('lists certificates', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['certificates' => [certificateMockData()]]),
        ]);

        $resource = new CertificateResource($connector);
        $result = $resource->list(1, 1);

        expect($result)
            ->toBeInstanceOf(CertificateCollectionData::class)
            ->certificates->toHaveCount(1);
    });

    it('gets a certificate', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['certificate' => certificateMockData()]),
        ]);

        $resource = new CertificateResource($connector);
        $result = $resource->get(1, 1, 1);

        expect($result)
            ->toBeInstanceOf(CertificateData::class)
            ->id->toBe(1)
            ->domain->toBe('test.com')
            ->active->toBeTrue();
    });

    it('obtains lets encrypt certificate', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['certificate' => certificateMockData()]),
        ]);

        $resource = new CertificateResource($connector);
        $data = ObtainLetsEncryptCertificateData::from(['domains' => ['test.com']]);
        $result = $resource->obtainLetsEncrypt(1, 1, $data);

        expect($result)
            ->toBeInstanceOf(CertificateData::class)
            ->type->toBe('letsencrypt');
    });

    it('activates a certificate', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new CertificateResource($connector);
        $resource->activate(1, 1, 1);

        $mockClient->assertSentCount(1);
    });

    it('deletes a certificate', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new CertificateResource($connector);
        $resource->delete(1, 1, 1);

        $mockClient->assertSentCount(1);
    });

    it('gets signing request', function (): void {
        $connector = createMockedConnector([
            MockResponse::make('-----BEGIN CERTIFICATE REQUEST-----'),
        ]);

        $resource = new CertificateResource($connector);
        $result = $resource->signingRequest(1, 1, 1);

        expect($result)->toBe('-----BEGIN CERTIFICATE REQUEST-----');
    });
});

describe('DaemonResource', function (): void {
    it('lists daemons', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['daemons' => [daemonMockData()]]),
        ]);

        $resource = new DaemonResource($connector);
        $result = $resource->list(1);

        expect($result)
            ->toBeInstanceOf(DaemonCollectionData::class)
            ->daemons->toHaveCount(1);
    });

    it('gets a daemon', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['daemon' => daemonMockData()]),
        ]);

        $resource = new DaemonResource($connector);
        $result = $resource->get(1, 1);

        expect($result)
            ->toBeInstanceOf(DaemonData::class)
            ->id->toBe(1)
            ->command->toBe('php artisan queue:work');
    });

    it('creates a daemon', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['daemon' => daemonMockData()]),
        ]);

        $resource = new DaemonResource($connector);
        $data = CreateDaemonData::from([
            'command' => 'php artisan queue:work',
            'directory' => '/home/forge/test.com',
        ]);
        $result = $resource->create(1, $data);

        expect($result)
            ->toBeInstanceOf(DaemonData::class)
            ->command->toBe('php artisan queue:work');
    });

    it('restarts a daemon', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new DaemonResource($connector);
        $resource->restart(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('deletes a daemon', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new DaemonResource($connector);
        $resource->delete(1, 1);

        $mockClient->assertSentCount(1);
    });
});

describe('FirewallResource', function (): void {
    it('lists firewall rules', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['rules' => [firewallRuleMockData()]]),
        ]);

        $resource = new FirewallResource($connector);
        $result = $resource->list(1);

        expect($result)
            ->toBeInstanceOf(FirewallRuleCollectionData::class)
            ->rules->toHaveCount(1);
    });

    it('gets a firewall rule', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['rule' => firewallRuleMockData()]),
        ]);

        $resource = new FirewallResource($connector);
        $result = $resource->get(1, 1);

        expect($result)
            ->toBeInstanceOf(FirewallRuleData::class)
            ->id->toBe(1)
            ->name->toBe('Allow SSH')
            ->port->toBe(22);
    });

    it('creates a firewall rule', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['rule' => firewallRuleMockData()]),
        ]);

        $resource = new FirewallResource($connector);
        $data = CreateFirewallRuleData::from([
            'name' => 'Allow SSH',
            'port' => 22,
        ]);
        $result = $resource->create(1, $data);

        expect($result)
            ->toBeInstanceOf(FirewallRuleData::class)
            ->name->toBe('Allow SSH');
    });

    it('deletes a firewall rule', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new FirewallResource($connector);
        $resource->delete(1, 1);

        $mockClient->assertSentCount(1);
    });
});

describe('JobResource', function (): void {
    it('lists jobs', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['jobs' => [jobMockData()]]),
        ]);

        $resource = new JobResource($connector);
        $result = $resource->list(1);

        expect($result)
            ->toBeInstanceOf(JobCollectionData::class)
            ->jobs->toHaveCount(1);
    });

    it('gets a job', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['job' => jobMockData()]),
        ]);

        $resource = new JobResource($connector);
        $result = $resource->get(1, 1);

        expect($result)
            ->toBeInstanceOf(JobData::class)
            ->id->toBe(1)
            ->command->toBe('php artisan schedule:run');
    });

    it('creates a job', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['job' => jobMockData()]),
        ]);

        $resource = new JobResource($connector);
        $data = CreateJobData::from([
            'command' => 'php artisan schedule:run',
            'frequency' => 'minutely',
        ]);
        $result = $resource->create(1, $data);

        expect($result)
            ->toBeInstanceOf(JobData::class)
            ->command->toBe('php artisan schedule:run');
    });

    it('deletes a job', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new JobResource($connector);
        $resource->delete(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('gets job output', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['output' => 'Job output content']),
        ]);

        $resource = new JobResource($connector);
        $result = $resource->getOutput(1, 1);

        expect($result)->toBe('Job output content');
    });
});

describe('MonitorResource', function (): void {
    it('lists monitors', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['monitors' => [monitorMockData()]]),
        ]);

        $resource = new MonitorResource($connector);
        $result = $resource->list(1);

        expect($result)
            ->toBeInstanceOf(MonitorCollectionData::class)
            ->monitors->toHaveCount(1);
    });

    it('gets a monitor', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['monitor' => monitorMockData()]),
        ]);

        $resource = new MonitorResource($connector);
        $result = $resource->get(1, 1);

        expect($result)
            ->toBeInstanceOf(MonitorData::class)
            ->id->toBe(1)
            ->type->toBe('disk')
            ->threshold->toBe(80);
    });

    it('creates a monitor', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['monitor' => monitorMockData()]),
        ]);

        $resource = new MonitorResource($connector);
        $data = CreateMonitorData::from(['type' => 'disk']);
        $result = $resource->create(1, $data);

        expect($result)
            ->toBeInstanceOf(MonitorData::class)
            ->type->toBe('disk');
    });

    it('deletes a monitor', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new MonitorResource($connector);
        $resource->delete(1, 1);

        $mockClient->assertSentCount(1);
    });
});

describe('WorkerResource', function (): void {
    it('lists workers', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['workers' => [workerMockData()]]),
        ]);

        $resource = new WorkerResource($connector);
        $result = $resource->list(1, 1);

        expect($result)
            ->toBeInstanceOf(WorkerCollectionData::class)
            ->workers->toHaveCount(1);
    });

    it('gets a worker', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['worker' => workerMockData()]),
        ]);

        $resource = new WorkerResource($connector);
        $result = $resource->get(1, 1, 1);

        expect($result)
            ->toBeInstanceOf(WorkerData::class)
            ->id->toBe(1)
            ->connection->toBe('redis')
            ->queue->toBe('default');
    });

    it('creates a worker', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['worker' => workerMockData()]),
        ]);

        $resource = new WorkerResource($connector);
        $data = CreateWorkerData::from([
            'connection' => 'redis',
            'queue' => 'default',
        ]);
        $result = $resource->create(1, 1, $data);

        expect($result)
            ->toBeInstanceOf(WorkerData::class)
            ->connection->toBe('redis');
    });

    it('restarts a worker', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new WorkerResource($connector);
        $resource->restart(1, 1, 1);

        $mockClient->assertSentCount(1);
    });

    it('deletes a worker', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new WorkerResource($connector);
        $resource->delete(1, 1, 1);

        $mockClient->assertSentCount(1);
    });

    it('gets worker output', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['output' => 'Worker output content']),
        ]);

        $resource = new WorkerResource($connector);
        $result = $resource->getOutput(1, 1, 1);

        expect($result)->toBe('Worker output content');
    });
});

describe('WebhookResource', function (): void {
    it('lists webhooks', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['webhooks' => [webhookMockData()]]),
        ]);

        $resource = new WebhookResource($connector);
        $result = $resource->list(1, 1);

        expect($result)
            ->toBeInstanceOf(WebhookCollectionData::class)
            ->webhooks->toHaveCount(1);
    });

    it('gets a webhook', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['webhook' => webhookMockData()]),
        ]);

        $resource = new WebhookResource($connector);
        $result = $resource->get(1, 1, 1);

        expect($result)
            ->toBeInstanceOf(WebhookData::class)
            ->id->toBe(1)
            ->url->toBe('https://example.com/webhook');
    });

    it('creates a webhook', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['webhook' => webhookMockData()]),
        ]);

        $resource = new WebhookResource($connector);
        $data = CreateWebhookData::from(['url' => 'https://example.com/webhook']);
        $result = $resource->create(1, 1, $data);

        expect($result)
            ->toBeInstanceOf(WebhookData::class)
            ->url->toBe('https://example.com/webhook');
    });

    it('deletes a webhook', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new WebhookResource($connector);
        $resource->delete(1, 1, 1);

        $mockClient->assertSentCount(1);
    });
});

describe('SSHKeyResource', function (): void {
    it('lists ssh keys', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['keys' => [sshKeyMockData()]]),
        ]);

        $resource = new SSHKeyResource($connector);
        $result = $resource->list(1);

        expect($result)
            ->toBeInstanceOf(SSHKeyCollectionData::class)
            ->keys->toHaveCount(1);
    });

    it('gets an ssh key', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['key' => sshKeyMockData()]),
        ]);

        $resource = new SSHKeyResource($connector);
        $result = $resource->get(1, 1);

        expect($result)
            ->toBeInstanceOf(SSHKeyData::class)
            ->id->toBe(1)
            ->name->toBe('test-key');
    });

    it('creates an ssh key', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['key' => sshKeyMockData()]),
        ]);

        $resource = new SSHKeyResource($connector);
        $data = CreateSSHKeyData::from([
            'name' => 'test-key',
            'key' => 'ssh-rsa AAAA...',
        ]);
        $result = $resource->create(1, $data);

        expect($result)
            ->toBeInstanceOf(SSHKeyData::class)
            ->name->toBe('test-key');
    });

    it('deletes an ssh key', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SSHKeyResource($connector);
        $resource->delete(1, 1);

        $mockClient->assertSentCount(1);
    });
});

describe('RedirectRuleResource', function (): void {
    it('lists redirect rules', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['redirect_rules' => [redirectRuleMockData()]]),
        ]);

        $resource = new RedirectRuleResource($connector);
        $result = $resource->list(1, 1);

        expect($result)
            ->toBeInstanceOf(RedirectRuleCollectionData::class);
    });

    it('gets a redirect rule', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['rule' => redirectRuleMockData()]),
        ]);

        $resource = new RedirectRuleResource($connector);
        $result = $resource->get(1, 1, 1);

        expect($result)
            ->toBeInstanceOf(RedirectRuleData::class)
            ->id->toBe(1)
            ->from->toBe('/old')
            ->to->toBe('/new');
    });

    it('creates a redirect rule', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['rule' => redirectRuleMockData()]),
        ]);

        $resource = new RedirectRuleResource($connector);
        $data = CreateRedirectRuleData::from([
            'from' => '/old',
            'to' => '/new',
        ]);
        $result = $resource->create(1, 1, $data);

        expect($result)
            ->toBeInstanceOf(RedirectRuleData::class)
            ->from->toBe('/old');
    });

    it('deletes a redirect rule', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new RedirectRuleResource($connector);
        $resource->delete(1, 1, 1);

        $mockClient->assertSentCount(1);
    });
});

describe('SecurityRuleResource', function (): void {
    it('lists security rules', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['security_rules' => [securityRuleMockData()]]),
        ]);

        $resource = new SecurityRuleResource($connector);
        $result = $resource->list(1, 1);

        expect($result)
            ->toBeInstanceOf(SecurityRuleCollectionData::class)
            ->rules->toHaveCount(1);
    });

    it('gets a security rule', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['rule' => securityRuleMockData()]),
        ]);

        $resource = new SecurityRuleResource($connector);
        $result = $resource->get(1, 1, 1);

        expect($result)
            ->toBeInstanceOf(SecurityRuleData::class)
            ->id->toBe(1)
            ->name->toBe('Admin Panel')
            ->path->toBe('/admin');
    });

    it('creates a security rule', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['rule' => securityRuleMockData()]),
        ]);

        $resource = new SecurityRuleResource($connector);
        $data = CreateSecurityRuleData::from([
            'name' => 'Admin Panel',
            'path' => '/admin',
        ]);
        $result = $resource->create(1, 1, $data);

        expect($result)
            ->toBeInstanceOf(SecurityRuleData::class)
            ->name->toBe('Admin Panel');
    });

    it('deletes a security rule', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new SecurityRuleResource($connector);
        $resource->delete(1, 1, 1);

        $mockClient->assertSentCount(1);
    });
});

describe('BackupResource', function (): void {
    it('lists backup configurations', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['backups' => [backupConfigurationMockData()]]),
        ]);

        $resource = new BackupResource($connector);
        $result = $resource->listConfigurations(1);

        expect($result)
            ->toBeInstanceOf(BackupConfigurationCollectionData::class)
            ->backups->toHaveCount(1);
    });

    it('gets a backup configuration', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['backup' => backupConfigurationMockData()]),
        ]);

        $resource = new BackupResource($connector);
        $result = $resource->getConfiguration(1, 1);

        expect($result)
            ->toBeInstanceOf(BackupConfigurationData::class)
            ->id->toBe(1)
            ->provider->toBe('s3');
    });

    it('creates a backup configuration', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['backup' => backupConfigurationMockData()]),
        ]);

        $resource = new BackupResource($connector);
        $data = CreateBackupConfigurationData::from(['provider' => 's3']);
        $result = $resource->createConfiguration(1, $data);

        expect($result)
            ->toBeInstanceOf(BackupConfigurationData::class)
            ->provider->toBe('s3');
    });

    it('updates a backup configuration', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['backup' => backupConfigurationMockData(['time' => '06:00'])]),
        ]);

        $resource = new BackupResource($connector);
        $data = UpdateBackupConfigurationData::from(['time' => '06:00']);
        $result = $resource->updateConfiguration(1, 1, $data);

        expect($result)
            ->toBeInstanceOf(BackupConfigurationData::class)
            ->time->toBe('06:00');
    });

    it('deletes a backup configuration', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new BackupResource($connector);
        $resource->deleteConfiguration(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('restores a backup', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new BackupResource($connector);
        $resource->restore(1, 1, 1);

        $mockClient->assertSentCount(1);
    });

    it('deletes a backup', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new BackupResource($connector);
        $resource->delete(1, 1, 1);

        $mockClient->assertSentCount(1);
    });
});

describe('NginxTemplateResource', function (): void {
    it('lists nginx templates', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['templates' => [nginxTemplateMockData()]]),
        ]);

        $resource = new NginxTemplateResource($connector);
        $result = $resource->list(1);

        expect($result)
            ->toBeInstanceOf(NginxTemplateCollectionData::class)
            ->templates->toHaveCount(1);
    });

    it('gets a nginx template', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['template' => nginxTemplateMockData()]),
        ]);

        $resource = new NginxTemplateResource($connector);
        $result = $resource->get(1, 1);

        expect($result)
            ->toBeInstanceOf(NginxTemplateData::class)
            ->id->toBe(1)
            ->name->toBe('Default');
    });

    it('gets default nginx template', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['template' => nginxTemplateMockData(['name' => 'Default Template'])]),
        ]);

        $resource = new NginxTemplateResource($connector);
        $result = $resource->default(1);

        expect($result)
            ->toBeInstanceOf(NginxTemplateData::class)
            ->name->toBe('Default Template');
    });

    it('creates a nginx template', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['template' => nginxTemplateMockData()]),
        ]);

        $resource = new NginxTemplateResource($connector);
        $data = CreateNginxTemplateData::from([
            'name' => 'Default',
            'content' => 'server { listen 80; }',
        ]);
        $result = $resource->create(1, $data);

        expect($result)
            ->toBeInstanceOf(NginxTemplateData::class)
            ->name->toBe('Default');
    });

    it('updates a nginx template', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['template' => nginxTemplateMockData(['name' => 'Updated'])]),
        ]);

        $resource = new NginxTemplateResource($connector);
        $data = UpdateNginxTemplateData::from(['name' => 'Updated']);
        $result = $resource->update(1, 1, $data);

        expect($result)
            ->toBeInstanceOf(NginxTemplateData::class)
            ->name->toBe('Updated');
    });

    it('deletes a nginx template', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new NginxTemplateResource($connector);
        $resource->delete(1, 1);

        $mockClient->assertSentCount(1);
    });
});

describe('UserResource', function (): void {
    it('gets the authenticated user', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['user' => userMockData()]),
        ]);

        $resource = new UserResource($connector);
        $result = $resource->get();

        expect($result)
            ->toBeInstanceOf(UserData::class)
            ->id->toBe(1)
            ->name->toBe('John Doe')
            ->email->toBe('john@example.com');
    });
});

describe('ServiceResource', function (): void {
    it('reboots mysql', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServiceResource($connector);
        $resource->rebootMysql(1);

        $mockClient->assertSentCount(1);
    });

    it('stops mysql', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServiceResource($connector);
        $resource->stopMysql(1);

        $mockClient->assertSentCount(1);
    });

    it('reboots nginx', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServiceResource($connector);
        $resource->rebootNginx(1);

        $mockClient->assertSentCount(1);
    });

    it('stops nginx', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServiceResource($connector);
        $resource->stopNginx(1);

        $mockClient->assertSentCount(1);
    });

    it('tests nginx', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['result' => 'success']),
        ]);

        $resource = new ServiceResource($connector);
        $result = $resource->testNginx(1);

        expect($result)
            ->toBeArray()
            ->toHaveKey('result', 'success');
    });

    it('reboots postgres', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServiceResource($connector);
        $resource->rebootPostgres(1);

        $mockClient->assertSentCount(1);
    });

    it('stops postgres', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServiceResource($connector);
        $resource->stopPostgres(1);

        $mockClient->assertSentCount(1);
    });

    it('reboots php', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServiceResource($connector);
        $resource->rebootPhp(1);

        $mockClient->assertSentCount(1);
    });

    it('installs blackfire', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServiceResource($connector);
        $resource->installBlackfire(1, 'server-id-token', 'server-token');

        $mockClient->assertSentCount(1);
    });

    it('removes blackfire', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServiceResource($connector);
        $resource->removeBlackfire(1);

        $mockClient->assertSentCount(1);
    });

    it('installs papertrail', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServiceResource($connector);
        $resource->installPapertrail(1, 'logs.papertrail.com:12345');

        $mockClient->assertSentCount(1);
    });

    it('removes papertrail', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServiceResource($connector);
        $resource->removePapertrail(1);

        $mockClient->assertSentCount(1);
    });

    it('starts a service', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServiceResource($connector);
        $resource->startService(1, 'nginx');

        $mockClient->assertSentCount(1);
    });

    it('stops a service', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServiceResource($connector);
        $resource->stopService(1, 'nginx');

        $mockClient->assertSentCount(1);
    });

    it('restarts a service', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new ServiceResource($connector);
        $resource->restartService(1, 'nginx');

        $mockClient->assertSentCount(1);
    });
});

describe('PhpResource', function (): void {
    it('lists php versions', function (): void {
        $connector = createMockedConnector([
            MockResponse::make([['version' => 'php84', 'status' => 'installed']]),
        ]);

        $resource = new PhpResource($connector);
        $result = $resource->list(1);

        expect($result)
            ->toBeArray()
            ->toHaveCount(1);
    });

    it('installs php version', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new PhpResource($connector);
        $resource->install(1, 'php84');

        $mockClient->assertSentCount(1);
    });

    it('updates php version', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new PhpResource($connector);
        $resource->update(1, 'php84');

        $mockClient->assertSentCount(1);
    });

    it('enables opcache', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new PhpResource($connector);
        $resource->enableOpcache(1);

        $mockClient->assertSentCount(1);
    });

    it('disables opcache', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new PhpResource($connector);
        $resource->disableOpcache(1);

        $mockClient->assertSentCount(1);
    });
});

describe('IntegrationResource', function (): void {
    it('gets horizon status', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['enabled' => true]),
        ]);

        $resource = new IntegrationResource($connector);
        $result = $resource->getHorizon(1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveKey('enabled', true);
    });

    it('enables horizon', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new IntegrationResource($connector);
        $resource->enableHorizon(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('disables horizon', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new IntegrationResource($connector);
        $resource->disableHorizon(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('gets octane status', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['enabled' => false]),
        ]);

        $resource = new IntegrationResource($connector);
        $result = $resource->getOctane(1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveKey('enabled', false);
    });

    it('enables octane', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new IntegrationResource($connector);
        $resource->enableOctane(1, 1, 'swoole', 8000);

        $mockClient->assertSentCount(1);
    });

    it('disables octane', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new IntegrationResource($connector);
        $resource->disableOctane(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('gets reverb status', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['enabled' => true]),
        ]);

        $resource = new IntegrationResource($connector);
        $result = $resource->getReverb(1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveKey('enabled', true);
    });

    it('enables reverb', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new IntegrationResource($connector);
        $resource->enableReverb(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('disables reverb', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new IntegrationResource($connector);
        $resource->disableReverb(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('gets pulse status', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['enabled' => true]),
        ]);

        $resource = new IntegrationResource($connector);
        $result = $resource->getPulse(1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveKey('enabled', true);
    });

    it('enables pulse', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new IntegrationResource($connector);
        $resource->enablePulse(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('disables pulse', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new IntegrationResource($connector);
        $resource->disablePulse(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('gets inertia status', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['enabled' => false]),
        ]);

        $resource = new IntegrationResource($connector);
        $result = $resource->getInertia(1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveKey('enabled', false);
    });

    it('enables inertia', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new IntegrationResource($connector);
        $resource->enableInertia(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('disables inertia', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new IntegrationResource($connector);
        $resource->disableInertia(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('gets maintenance status', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['enabled' => false]),
        ]);

        $resource = new IntegrationResource($connector);
        $result = $resource->getMaintenance(1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveKey('enabled', false);
    });

    it('enables maintenance', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new IntegrationResource($connector);
        $resource->enableMaintenance(1, 1, 'my-secret', 503);

        $mockClient->assertSentCount(1);
    });

    it('disables maintenance', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new IntegrationResource($connector);
        $resource->disableMaintenance(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('gets scheduler status', function (): void {
        $connector = createMockedConnector([
            MockResponse::make(['enabled' => true]),
        ]);

        $resource = new IntegrationResource($connector);
        $result = $resource->getScheduler(1, 1);

        expect($result)
            ->toBeArray()
            ->toHaveKey('enabled', true);
    });

    it('enables scheduler', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new IntegrationResource($connector);
        $resource->enableScheduler(1, 1);

        $mockClient->assertSentCount(1);
    });

    it('disables scheduler', function (): void {
        [$connector, $mockClient] = createMockedConnectorWithClient([
            MockResponse::make([], 200),
        ]);

        $resource = new IntegrationResource($connector);
        $resource->disableScheduler(1, 1);

        $mockClient->assertSentCount(1);
    });
});
