<?php

declare(strict_types=1);

use App\Mcp\Prompts\DeployLaravelAppPrompt;
use App\Mcp\Resources\{DeploymentGuidelinesResource, ForgeApiDocsResource};
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Certificates\{ObtainLetsEncryptCertificateTool};
use App\Mcp\Tools\Servers\{GetServerTool, ListServersTool, RebootServerTool};
use App\Mcp\Tools\Sites\{ListSitesTool};
use App\Integrations\Forge\Data\Certificates\CertificateData;
use App\Integrations\Forge\Data\Daemons\DaemonData;
use App\Integrations\Forge\Data\Databases\{DatabaseData, DatabaseUserData};
use App\Integrations\Forge\Data\Firewall\FirewallRuleData;
use App\Integrations\Forge\Data\Jobs\JobData;
use App\Integrations\Forge\Data\Servers\{ServerCollectionData, ServerData};
use App\Integrations\Forge\Data\Sites\{SiteCollectionData, SiteData};
use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Resources\{CertificateResource, ServerResource, SiteResource};

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

// Helper functions to create mock DTOs
function createTestServer(int $id, string $name, string $type = 'app', string $ip = '192.168.1.1', bool $ready = true): ServerData
{
    return ServerData::from([
        'id' => $id,
        'credential_id' => 1,
        'name' => $name,
        'type' => $type,
        'provider' => 'ocean2',
        'identifier' => "test-{$id}",
        'size' => '1gb',
        'region' => 'nyc1',
        'ubuntu_version' => '22.04',
        'db_status' => null,
        'redis_status' => null,
        'php_version' => '8.2',
        'php_cli_version' => '8.2',
        'opcache_status' => 'enabled',
        'database_type' => 'mysql8',
        'ip_address' => $ip,
        'ssh_port' => 22,
        'private_ip_address' => '10.0.0.1',
        'local_public_key' => 'ssh-rsa...',
        'blackfire_status' => null,
        'papertrail_status' => null,
        'revoked' => false,
        'created_at' => '2024-01-01T00:00:00Z',
        'is_ready' => $ready,
        'tags' => [],
        'network' => [],
    ]);
}

function createTestSite(int $id, int $serverId, string $name): SiteData
{
    return SiteData::from([
        'id' => $id,
        'server_id' => $serverId,
        'name' => $name,
        'aliases' => [],
        'directory' => '/home/forge/' . $name,
        'wildcards' => false,
        'status' => 'installed',
        'repository' => 'git@github.com:test/repo.git',
        'repository_provider' => 'github',
        'repository_branch' => 'main',
        'repository_status' => 'installed',
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
        'php_version' => '8.2',
        'tags' => [],
        'failure_deployment_emails' => [],
        'telegram_secret' => null,
        'web_directory' => '/public',
    ]);
}

function createTestDatabase(int $id, int $serverId, string $name): DatabaseData
{
    return DatabaseData::from([
        'id' => $id,
        'server_id' => $serverId,
        'name' => $name,
        'status' => 'installed',
        'created_at' => '2024-01-01T00:00:00Z',
    ]);
}

function createTestDatabaseUser(int $id, int $serverId, string $name, array $databases = [], string $status = 'creating'): DatabaseUserData
{
    return DatabaseUserData::from([
        'id' => $id,
        'server_id' => $serverId,
        'name' => $name,
        'status' => $status,
        'created_at' => '2024-01-01T00:00:00Z',
        'databases' => $databases,
    ]);
}

function createTestCertificate(int $id, int $serverId, int $siteId, string $domain, string $type = 'letsencrypt', string $status = 'installed'): CertificateData
{
    return CertificateData::from([
        'id' => $id,
        'server_id' => $serverId,
        'site_id' => $siteId,
        'domain' => $domain,
        'request_status' => 'created',
        'status' => $status,
        'type' => $type,
        'active' => true,
        'existing' => false,
        'expires_at' => '2025-01-01T00:00:00Z',
        'created_at' => '2024-01-01T00:00:00Z',
        'activation_error' => null,
    ]);
}

function createTestJob(int $id, int $serverId, string $command, string $frequency = 'minutely', string $user = 'forge', string $cron = '* * * * *', string $status = 'installed'): JobData
{
    return JobData::from([
        'id' => $id,
        'server_id' => $serverId,
        'command' => $command,
        'user' => $user,
        'frequency' => $frequency,
        'cron' => $cron,
        'status' => $status,
        'created_at' => '2024-01-01T00:00:00Z',
    ]);
}

function createTestDaemon(int $id, int $serverId, string $command, string $directory, string $user = 'forge', string $status = 'installed'): DaemonData
{
    return DaemonData::from([
        'id' => $id,
        'server_id' => $serverId,
        'command' => $command,
        'user' => $user,
        'status' => $status,
        'directory' => $directory,
        'created_at' => '2024-01-01T00:00:00Z',
    ]);
}

function createTestFirewallRule(int $id, int $serverId, string $name, int $port, ?string $ipAddress = null, string $status = 'installed'): FirewallRuleData
{
    return FirewallRuleData::from([
        'id' => $id,
        'server_id' => $serverId,
        'name' => $name,
        'port' => $port,
        'ip_address' => $ipAddress,
        'status' => $status,
        'created_at' => '2024-01-01T00:00:00Z',
    ]);
}

describe('ForgeServer', function (): void {
    it('has registered tools', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $collection = new ServerCollectionData(servers: []);
            $serverResource->shouldReceive('list')->once()->andReturn($collection);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response->assertOk();
    });
});

describe('ListServersTool', function (): void {
    it('lists servers successfully', function (): void {
        $mockServer = createTestServer(1, 'test-server');

        $this->mock(ForgeClient::class, function ($mock) use ($mockServer): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $collection = new ServerCollectionData(servers: [$mockServer]);
            $serverResource->shouldReceive('list')->once()->andReturn($collection);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response
            ->assertOk()
            ->assertSee('success')
            ->assertSee('test-server')
            ->assertSee('192.168.1.1')
            ->assertSee('nyc1');
    });

    it('returns empty list when no servers exist', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $collection = new ServerCollectionData(servers: []);
            $serverResource->shouldReceive('list')->once()->andReturn($collection);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response
            ->assertOk()
            ->assertSee('"count": 0')
            ->assertSee('"servers": []');
    });

    it('lists multiple servers', function (): void {
        $servers = [
            createTestServer(1, 'server-1', 'app', '1.1.1.1', true),
            createTestServer(2, 'server-2', 'web', '2.2.2.2', false),
            createTestServer(3, 'server-3', 'database', '3.3.3.3', true),
        ];

        $this->mock(ForgeClient::class, function ($mock) use ($servers): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $collection = new ServerCollectionData(servers: $servers);
            $serverResource->shouldReceive('list')->once()->andReturn($collection);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response
            ->assertOk()
            ->assertSee('"count": 3')
            ->assertSee('server-1')
            ->assertSee('server-2')
            ->assertSee('server-3');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('list')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('API Error');
    });

    it('handles network timeout errors', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('list')->once()->andThrow(new Exception('Connection timed out'));
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response
            ->assertOk()
            ->assertSee('Connection timed out');
    });

    it('handles authentication errors', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('list')->once()->andThrow(new Exception('Unauthorized: Invalid API token'));
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response
            ->assertOk()
            ->assertSee('Unauthorized');
    });
});

describe('GetServerTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(GetServerTool::class, []);

        $response->assertHasErrors();
    });

    it('rejects invalid server_id type', function (): void {
        $response = ForgeServer::tool(GetServerTool::class, [
            'server_id' => 'invalid',
        ]);

        $response->assertHasErrors();
    });

    it('rejects negative server_id', function (): void {
        $response = ForgeServer::tool(GetServerTool::class, [
            'server_id' => -1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects zero server_id', function (): void {
        $response = ForgeServer::tool(GetServerTool::class, [
            'server_id' => 0,
        ]);

        $response->assertHasErrors();
    });

    it('gets server details successfully', function (): void {
        $mockServer = createTestServer(1, 'test-server');

        $this->mock(ForgeClient::class, function ($mock) use ($mockServer): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('get')->with(Mockery::any())->once()->andReturn($mockServer);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(GetServerTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('test-server')
            ->assertSee('192.168.1.1')
            ->assertSee('10.0.0.1')
            ->assertSee('nyc1')
            ->assertSee('8.2');
    });

    it('handles server not found error', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('get')->with(Mockery::any())->once()->andThrow(new Exception('Server not found'));
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(GetServerTool::class, [
            'server_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Server not found');
    });

    it('returns server with network and tags', function (): void {
        $mockServer = ServerData::from([
            'id' => 1,
            'credential_id' => 1,
            'name' => 'networked-server',
            'type' => 'app',
            'provider' => 'ocean2',
            'identifier' => 'test-1',
            'size' => '1gb',
            'region' => 'nyc1',
            'ubuntu_version' => '22.04',
            'db_status' => null,
            'redis_status' => null,
            'php_version' => '8.2',
            'php_cli_version' => '8.2',
            'opcache_status' => 'enabled',
            'database_type' => 'mysql8',
            'ip_address' => '192.168.1.1',
            'ssh_port' => 22,
            'private_ip_address' => '10.0.0.1',
            'local_public_key' => 'ssh-rsa...',
            'blackfire_status' => null,
            'papertrail_status' => null,
            'revoked' => false,
            'created_at' => '2024-01-01T00:00:00Z',
            'is_ready' => true,
            'tags' => [['id' => 1, 'name' => 'production']],
            'network' => [2, 3, 4],
        ]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockServer): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('get')->with(Mockery::any())->once()->andReturn($mockServer);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(GetServerTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('networked-server')
            ->assertSee('production');
    });
});

describe('ListSitesTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListSitesTool::class, []);

        $response->assertHasErrors();
    });

    it('lists sites successfully', function (): void {
        $mockSite = createTestSite(1, 1, 'example.com');

        $this->mock(ForgeClient::class, function ($mock) use ($mockSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $collection = new SiteCollectionData(sites: [$mockSite]);
            $siteResource->shouldReceive('list')->with(Mockery::any())->once()->andReturn($collection);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ListSitesTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('example.com')
            ->assertSee('main')
            ->assertSee('php');
    });

    it('handles server not found error', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('reboot')->with(Mockery::any())->once()->andThrow(new Exception('Server not found'));
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(RebootServerTool::class, [
            'server_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Server not found');
    });

    it('handles permission denied error', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('reboot')->with(Mockery::any())->once()->andThrow(new Exception('Permission denied'));
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(RebootServerTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('Permission denied');
    });
});

describe('ObtainLetsEncryptCertificateTool', function (): void {
    it('requires server_id, site_id and domains parameters', function (): void {
        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, []);

        $response->assertHasErrors();
    });

    it('requires domains when server_id and site_id provided', function (): void {
        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('obtains certificate successfully', function (): void {
        $mockCert = CertificateData::from([
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'domain' => 'example.com',
            'request_status' => 'creating',
            'status' => 'installing',
            'type' => 'letsencrypt',
            'active' => false,
            'existing' => false,
            'expires_at' => null,
            'created_at' => '2024-01-01T00:00:00Z',
            'activation_error' => null,
        ]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockCert): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('obtainLetsEncrypt')
                ->with(1, 1, Mockery::type(App\Integrations\Forge\Data\Certificates\ObtainLetsEncryptCertificateData::class))
                ->once()
                ->andReturn($mockCert);
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'domains' => ['example.com'],
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('initiated');
    });

    it('obtains certificate for multiple domains', function (): void {
        $mockCert = CertificateData::from([
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'domain' => 'example.com',
            'request_status' => 'creating',
            'status' => 'installing',
            'type' => 'letsencrypt',
            'active' => false,
            'existing' => false,
            'expires_at' => null,
            'created_at' => '2024-01-01T00:00:00Z',
            'activation_error' => null,
        ]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockCert): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('obtainLetsEncrypt')
                ->with(1, 1, Mockery::type(App\Integrations\Forge\Data\Certificates\ObtainLetsEncryptCertificateData::class))
                ->once()
                ->andReturn($mockCert);
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'domains' => ['example.com', 'www.example.com'],
        ]);

        $response
            ->assertOk()
            ->assertSee('initiated');
    });

    it('handles DNS validation error', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('obtainLetsEncrypt')
                ->with(1, 1, Mockery::type(App\Integrations\Forge\Data\Certificates\ObtainLetsEncryptCertificateData::class))
                ->once()
                ->andThrow(new Exception('DNS validation failed'));
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'domains' => ['invalid.example.com'],
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('DNS validation failed');
    });

    it('handles rate limit error', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('obtainLetsEncrypt')
                ->with(1, 1, Mockery::type(App\Integrations\Forge\Data\Certificates\ObtainLetsEncryptCertificateData::class))
                ->once()
                ->andThrow(new Exception('Rate limit exceeded'));
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'domains' => ['example.com'],
        ]);

        $response
            ->assertOk()
            ->assertSee('Rate limit exceeded');
    });
});

describe('Prompts with edge cases', function (): void {
    it('handles missing server parameter', function (): void {
        $response = ForgeServer::prompt(DeployLaravelAppPrompt::class, []);

        $response
            ->assertOk()
            ->assertSee('list-servers-tool');
    });

    it('handles show_logs false', function (): void {
        $response = ForgeServer::prompt(DeployLaravelAppPrompt::class, [
            'server_name' => 'my-server',
            'site_domain' => 'example.com',
            'show_logs' => 'false',
        ]);

        $response
            ->assertOk()
            ->assertSee('deploy-site-tool');
    });
});

describe('Resources content validation', function (): void {
    it('forge api docs contains all endpoints', function (): void {
        $response = ForgeServer::resource(ForgeApiDocsResource::class);

        $response
            ->assertOk()
            ->assertSee('GET /servers')
            ->assertSee('POST /servers')
            ->assertSee('sites')
            ->assertSee('certificates')
            ->assertSee('databases')
            ->assertSee('Rate Limiting');
    });

    it('deployment guidelines contains best practices', function (): void {
        $response = ForgeServer::resource(DeploymentGuidelinesResource::class);

        $response
            ->assertOk()
            ->assertSee('Environment Configuration')
            ->assertSee('Zero-Downtime')
            ->assertSee('Rollback Strategy')
            ->assertSee('Quick Deploy')
            ->assertSee('git pull');
    });
});
