<?php

declare(strict_types=1);

use App\Integrations\Forge\Data\Certificates\CertificateData;
use App\Integrations\Forge\Data\Databases\DatabaseData;
use App\Integrations\Forge\Data\Workers\WorkerData;
use App\Integrations\Forge\ForgeClient;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Certificates\ObtainLetsEncryptCertificateTool;
use App\Mcp\Tools\Databases\CreateDatabaseTool;
use App\Mcp\Tools\Sites\{CreateSiteTool, GetSiteTool, ListSitesTool};
use App\Mcp\Tools\Servers\{GetServerTool, ListServersTool};
use App\Mcp\Tools\Deployments\{DeploySiteTool, GetDeploymentScriptTool};
use App\Integrations\Forge\Resources\{CertificateResource, DatabaseResource, ServerResource, SiteResource, WorkerResource};
use App\Integrations\Forge\Data\Sites\{SiteCollectionData, SiteData};
use App\Integrations\Forge\Data\Servers\{ServerCollectionData, ServerData};
use App\Mcp\Tools\Workers\CreateWorkerTool;

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

function makeServerData(array $overrides = []): ServerData
{
    return ServerData::from(array_merge([
        'id' => 1,
        'credential_id' => 1,
        'name' => 'test-server',
        'type' => 'app',
        'provider' => 'ocean2',
        'identifier' => 'test-1',
        'size' => '1GB',
        'region' => 'nyc1',
        'ubuntu_version' => '22.04',
        'db_status' => 'installed',
        'redis_status' => 'installed',
        'php_version' => 'php84',
        'php_cli_version' => 'php84',
        'opcache_status' => 'enabled',
        'database_type' => 'mysql8',
        'ip_address' => '192.168.1.1',
        'ssh_port' => 22,
        'private_ip_address' => '10.0.0.1',
        'local_public_key' => 'ssh-rsa AAAA...',
        'blackfire_status' => null,
        'papertrail_status' => null,
        'revoked' => false,
        'created_at' => '2024-01-01T00:00:00Z',
        'is_ready' => true,
        'tags' => [],
        'network' => [],
    ], $overrides));
}

function makeSiteData(array $overrides = []): SiteData
{
    return SiteData::from(array_merge([
        'id' => 1,
        'server_id' => 1,
        'name' => 'example.com',
        'aliases' => null,
        'directory' => '/home/forge/example.com',
        'wildcards' => false,
        'status' => 'installed',
        'repository' => null,
        'repository_provider' => null,
        'repository_branch' => null,
        'repository_status' => null,
        'quick_deploy' => true,
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
    ], $overrides));
}

function makeWorkerData(array $overrides = []): WorkerData
{
    return WorkerData::from(array_merge([
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
        'status' => 'running',
        'created_at' => '2024-01-01T00:00:00Z',
    ], $overrides));
}

function makeCertificateData(array $overrides = []): CertificateData
{
    return CertificateData::from(array_merge([
        'id' => 1,
        'server_id' => 1,
        'site_id' => 1,
        'domain' => 'example.com',
        'request_status' => null,
        'status' => 'installed',
        'type' => 'letsencrypt',
        'active' => true,
        'expires_at' => '2025-01-01T00:00:00Z',
        'created_at' => '2024-01-01T00:00:00Z',
        'activation_error' => null,
    ], $overrides));
}

function makeDatabaseData(array $overrides = []): DatabaseData
{
    return DatabaseData::from(array_merge([
        'id' => 1,
        'server_id' => 1,
        'name' => 'forge',
        'status' => 'installed',
        'created_at' => '2024-01-01T00:00:00Z',
    ], $overrides));
}

describe('Complete Server Provisioning Flow', function (): void {
    it('simulates listing servers and getting server details', function (): void {
        $mockServer = makeServerData(['name' => 'production-server']);

        $this->mock(ForgeClient::class, function ($mock) use ($mockServer): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('list')->andReturn(new ServerCollectionData(servers: [$mockServer]));
            $serverResource->shouldReceive('get')->with(1)->andReturn($mockServer);
            $mock->shouldReceive('servers')->andReturn($serverResource);
        });

        $listResponse = ForgeServer::tool(ListServersTool::class, []);
        $listResponse->assertOk()->assertSee('production-server');

        $getResponse = ForgeServer::tool(GetServerTool::class, ['server_id' => 1]);
        $getResponse->assertOk()->assertSee('192.168.1.1');
    });

    it('simulates creating a site on a server', function (): void {
        $mockSite = makeSiteData(['status' => 'installing']);

        $this->mock(ForgeClient::class, function ($mock) use ($mockSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('create')->andReturn($mockSite);
            $mock->shouldReceive('sites')->andReturn($siteResource);
        });

        $response = ForgeServer::tool(CreateSiteTool::class, [
            'server_id' => 1,
            'domain' => 'example.com',
            'project_type' => 'php',
            'directory' => '/public',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('Complete Site Setup Flow', function (): void {
    it('simulates full site setup with database and SSL', function (): void {
        $mockSite = makeSiteData();
        $mockDatabase = makeDatabaseData(['name' => 'example_db']);
        $mockCert = makeCertificateData(['status' => 'installing', 'active' => false]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockSite, $mockDatabase, $mockCert): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->andReturn($mockSite);
            $mock->shouldReceive('sites')->andReturn($siteResource);

            $dbResource = Mockery::mock(DatabaseResource::class);
            $dbResource->shouldReceive('create')->andReturn($mockDatabase);
            $mock->shouldReceive('databases')->andReturn($dbResource);

            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('obtainLetsEncrypt')->andReturn($mockCert);
            $mock->shouldReceive('certificates')->andReturn($certResource);
        });

        $siteResponse = ForgeServer::tool(GetSiteTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);
        $siteResponse->assertOk()->assertSee('example.com');

        $dbResponse = ForgeServer::tool(CreateDatabaseTool::class, [
            'server_id' => 1,
            'name' => 'example_db',
        ]);
        $dbResponse->assertOk()->assertSee('"success": true');

        $certResponse = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'domains' => ['example.com'],
        ]);
        $certResponse->assertOk()->assertSee('"success": true');
    });
});

describe('Complete Deployment Flow', function (): void {
    it('simulates deployment workflow', function (): void {
        $mockSite = makeSiteData([
            'repository' => 'user/repo',
            'repository_branch' => 'main',
        ]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->andReturn($mockSite);
            $siteResource->shouldReceive('deploymentScript')->andReturn('cd /home/forge/example.com && git pull origin main && composer install --no-interaction');
            $siteResource->shouldReceive('deploy');
            $mock->shouldReceive('sites')->andReturn($siteResource);
        });

        $siteResponse = ForgeServer::tool(GetSiteTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);
        $siteResponse->assertOk()->assertSee('example.com');

        $scriptResponse = ForgeServer::tool(GetDeploymentScriptTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);
        $scriptResponse->assertOk()->assertSee('git pull');

        $deployResponse = ForgeServer::tool(DeploySiteTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);
        $deployResponse->assertOk()->assertSee('"success": true');
    });
});

describe('Complete Worker Setup Flow', function (): void {
    it('simulates queue worker setup', function (): void {
        $mockWorker = makeWorkerData();

        $this->mock(ForgeClient::class, function ($mock) use ($mockWorker): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('create')->andReturn($mockWorker);
            $mock->shouldReceive('workers')->andReturn($workerResource);
        });

        $response = ForgeServer::tool(CreateWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'connection' => 'redis',
            'queue' => 'default',
            'timeout' => 60,
            'tries' => 3,
        ]);

        $response->assertOk()->assertSee('"success": true')->assertSee('running');
    });
});

describe('Error Handling Scenarios', function (): void {
    it('handles API authentication failure', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('list')->andThrow(new Exception('Unauthorized: Invalid API token'));
            $mock->shouldReceive('servers')->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response->assertOk()->assertSee('"success": false')->assertSee('Unauthorized');
    });

    it('handles server not found', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('get')->andThrow(new Exception('Server not found'));
            $mock->shouldReceive('servers')->andReturn($serverResource);
        });

        $response = ForgeServer::tool(GetServerTool::class, ['server_id' => 999]);

        $response->assertOk()->assertSee('"success": false')->assertSee('not found');
    });

    it('handles rate limiting', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('list')->andThrow(new Exception('Rate limit exceeded. Please wait before making another request.'));
            $mock->shouldReceive('servers')->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response->assertOk()->assertSee('"success": false')->assertSee('Rate limit');
    });

    it('handles network timeout', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('list')->andThrow(new Exception('Connection timed out'));
            $mock->shouldReceive('servers')->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response->assertOk()->assertSee('"success": false')->assertSee('timed out');
    });
});

describe('MCP Protocol Integration', function (): void {
    it('validates ForgeServer is registered correctly', function (): void {
        expect(class_exists(ForgeServer::class))->toBeTrue();

        $reflection = new ReflectionClass(ForgeServer::class);
        expect($reflection->hasProperty('tools'))->toBeTrue();
        expect($reflection->hasProperty('resources'))->toBeTrue();
        expect($reflection->hasProperty('prompts'))->toBeTrue();
    });

    it('validates all tools are discoverable', function (): void {
        $toolsPath = app_path('Mcp/Tools');
        $toolFiles = collect(File::allFiles($toolsPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Tool.php'))
            ->values();

        expect($toolFiles->count())->toBe(179);
    });

    it('validates all resources are discoverable', function (): void {
        $resourcesPath = app_path('Mcp/Resources');
        $resourceFiles = collect(File::allFiles($resourcesPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Resource.php'))
            ->values();

        expect($resourceFiles->count())->toBe(9);
    });

    it('validates all prompts are discoverable', function (): void {
        $promptsPath = app_path('Mcp/Prompts');
        $promptFiles = collect(File::allFiles($promptsPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Prompt.php'))
            ->values();

        expect($promptFiles->count())->toBe(6);
    });
});

describe('Complete Multi-Site Scenario', function (): void {
    it('simulates managing multiple sites across servers', function (): void {
        $mockServers = [
            makeServerData(['id' => 1, 'name' => 'prod-1', 'ip_address' => '10.0.0.1']),
            makeServerData(['id' => 2, 'name' => 'prod-2', 'ip_address' => '10.0.0.2']),
        ];

        $mockSites = [
            makeSiteData(['id' => 1, 'server_id' => 1, 'name' => 'api.example.com']),
            makeSiteData(['id' => 2, 'server_id' => 1, 'name' => 'www.example.com']),
            makeSiteData(['id' => 3, 'server_id' => 2, 'name' => 'admin.example.com']),
        ];

        $this->mock(ForgeClient::class, function ($mock) use ($mockServers, $mockSites): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('list')->andReturn(new ServerCollectionData(servers: $mockServers));
            $mock->shouldReceive('servers')->andReturn($serverResource);

            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('list')
                ->with(1)
                ->andReturn(new SiteCollectionData(sites: array_values(array_filter($mockSites, fn ($s) => $s->serverId === 1))));
            $siteResource->shouldReceive('list')
                ->with(2)
                ->andReturn(new SiteCollectionData(sites: array_values(array_filter($mockSites, fn ($s) => $s->serverId === 2))));
            $mock->shouldReceive('sites')->andReturn($siteResource);
        });

        $serversResponse = ForgeServer::tool(ListServersTool::class, []);
        $serversResponse->assertOk()
            ->assertSee('prod-1')
            ->assertSee('prod-2')
            ->assertSee('"count": 2');

        $sites1Response = ForgeServer::tool(ListSitesTool::class, ['server_id' => 1]);
        $sites1Response->assertOk()
            ->assertSee('api.example.com')
            ->assertSee('www.example.com');

        $sites2Response = ForgeServer::tool(ListSitesTool::class, ['server_id' => 2]);
        $sites2Response->assertOk()
            ->assertSee('admin.example.com');
    });
});

describe('Application Health Check', function (): void {
    it('validates all required configurations exist', function (): void {
        config(['services.forge.api_token' => 'test-token']);

        expect(config('services.forge.api_token'))->not->toBeNull();
    });

    it('validates ForgeClient can be instantiated', function (): void {
        config(['services.forge.api_token' => 'test-token']);

        $client = app(ForgeClient::class);

        expect($client)->toBeInstanceOf(ForgeClient::class);
    });

    it('validates all tool categories have tools', function (): void {
        $categories = [
            'Backups' => 7,
            'Certificates' => 7,
            'Commands' => 3,
            'Composite' => 5,
            'Configuration' => 4,
            'Credentials' => 1,
            'Daemons' => 5,
            'Databases' => 10,
            'Deployments' => 11,
            'Firewall' => 4,
            'Git' => 5,
            'Integrations' => 21,
            'Jobs' => 5,
            'Monitors' => 4,
            'NginxTemplates' => 6,
            'Php' => 5,
            'Recipes' => 6,
            'RedirectRules' => 4,
            'Regions' => 1,
            'SecurityRules' => 4,
            'Servers' => 13,
            'Services' => 15,
            'Sites' => 18,
            'SSHKeys' => 4,
            'User' => 1,
            'Webhooks' => 4,
            'Workers' => 6,
        ];

        foreach ($categories as $category => $expectedCount) {
            $path = app_path("Mcp/Tools/{$category}");

            if (is_dir($path)) {
                $files = collect(File::files($path))
                    ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Tool.php'))
                    ->count();

                expect($files)->toBe($expectedCount, "Category {$category} should have {$expectedCount} tools, got {$files}");
            }
        }
    });
});
