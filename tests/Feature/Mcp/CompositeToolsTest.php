<?php

declare(strict_types=1);

use App\Integrations\Forge\Data\Daemons\DaemonCollectionData;
use App\Integrations\Forge\Data\Jobs\JobCollectionData;
use App\Integrations\Forge\Data\Monitors\MonitorCollectionData;
use App\Integrations\Forge\Data\Workers\WorkerCollectionData;
use App\Integrations\Forge\ForgeClient;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Composite\{BulkDeployTool, CloneSiteTool, SSLExpirationCheckTool, ServerHealthCheckTool, SiteStatusDashboardTool};
use App\Integrations\Forge\Resources\{CertificateResource, DaemonResource, JobResource, MonitorResource, ServerResource, SiteResource, WorkerResource};
use App\Integrations\Forge\Data\Sites\{SiteCollectionData, SiteData};
use App\Integrations\Forge\Data\Servers\{ServerCollectionData, ServerData};
use App\Integrations\Forge\Data\Certificates\{CertificateCollectionData, CertificateData};

function createMockServerData(array $overrides = []): ServerData
{
    return ServerData::from(array_merge([
        'id' => 1,
        'credential_id' => 1,
        'name' => 'test-server',
        'type' => 'app',
        'provider' => 'digitalocean',
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

function createMockSiteData(array $overrides = []): SiteData
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

function createMockCertificateData(array $overrides = []): CertificateData
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
        'expires_at' => now()->addDays(60)->toIso8601String(),
        'created_at' => '2024-01-01T00:00:00Z',
        'activation_error' => null,
    ], $overrides));
}

describe('ServerHealthCheckTool', function (): void {
    it('requires server_id parameter', function (): void {
        $this->mock(ForgeClient::class);

        $response = ForgeServer::tool(ServerHealthCheckTool::class, []);

        $response->assertHasErrors();
    });

    it('returns comprehensive health check data', function (): void {
        $mockServer = createMockServerData(['id' => 1, 'name' => 'test-server']);

        $this->mock(ForgeClient::class, function ($mock) use ($mockServer): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('get')->with(1)->once()->andReturn($mockServer);
            $serverResource->shouldReceive('listEvents')->with(1)->once()->andReturn([]);

            $monitorResource = Mockery::mock(MonitorResource::class);
            $monitorResource->shouldReceive('list')->with(1)->once()->andReturn(
                MonitorCollectionData::from(['monitors' => []])
            );

            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('list')->with(1)->once()->andReturn(
                SiteCollectionData::from(['sites' => []])
            );

            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('list')->with(1)->once()->andReturn(
                DaemonCollectionData::from(['daemons' => []])
            );

            $mock->shouldReceive('servers')->andReturn($serverResource);
            $mock->shouldReceive('monitors')->andReturn($monitorResource);
            $mock->shouldReceive('sites')->andReturn($siteResource);
            $mock->shouldReceive('daemons')->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(ServerHealthCheckTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('health_status')
            ->assertSee('test-server');
    });
});

describe('SiteStatusDashboardTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $this->mock(ForgeClient::class);

        $response = ForgeServer::tool(SiteStatusDashboardTool::class, []);

        $response->assertHasErrors();
    });

    it('returns comprehensive site dashboard', function (): void {
        $mockSite = createMockSiteData([
            'id' => 1,
            'name' => 'example.com',
            'repository' => 'user/repo',
            'repository_provider' => 'github',
            'repository_branch' => 'main',
        ]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->with(1, 1)->once()->andReturn($mockSite);
            $siteResource->shouldReceive('deploymentHistory')->with(1, 1)->once()->andReturn([]);

            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('list')->with(1, 1)->once()->andReturn(
                CertificateCollectionData::from(['certificates' => []])
            );

            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('list')->with(1, 1)->once()->andReturn(
                WorkerCollectionData::from(['workers' => []])
            );

            $jobResource = Mockery::mock(JobResource::class);
            $jobResource->shouldReceive('list')->with(1)->once()->andReturn(
                JobCollectionData::from(['jobs' => []])
            );

            $mock->shouldReceive('sites')->andReturn($siteResource);
            $mock->shouldReceive('certificates')->andReturn($certResource);
            $mock->shouldReceive('workers')->andReturn($workerResource);
            $mock->shouldReceive('jobs')->andReturn($jobResource);
        });

        $response = ForgeServer::tool(SiteStatusDashboardTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('example.com')
            ->assertSee('repository');
    });
});

describe('BulkDeployTool', function (): void {
    it('requires deployments array parameter', function (): void {
        $this->mock(ForgeClient::class);

        $response = ForgeServer::tool(BulkDeployTool::class, []);

        $response->assertHasErrors();
    });

    it('deploys multiple sites successfully', function (): void {
        $mockSite1 = createMockSiteData(['id' => 1, 'name' => 'site1.com']);
        $mockSite2 = createMockSiteData(['id' => 2, 'name' => 'site2.com']);

        $this->mock(ForgeClient::class, function ($mock) use ($mockSite1, $mockSite2): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->with(1, 1)->once()->andReturn($mockSite1);
            $siteResource->shouldReceive('get')->with(1, 2)->once()->andReturn($mockSite2);
            $siteResource->shouldReceive('deploy')->with(1, 1)->once();
            $siteResource->shouldReceive('deploy')->with(1, 2)->once();

            $mock->shouldReceive('sites')->andReturn($siteResource);
        });

        $response = ForgeServer::tool(BulkDeployTool::class, [
            'deployments' => [
                ['server_id' => 1, 'site_id' => 1],
                ['server_id' => 1, 'site_id' => 2],
            ],
        ]);

        $response
            ->assertOk()
            ->assertSee('successful')
            ->assertSee('site1.com')
            ->assertSee('site2.com');
    });

    it('handles partial failures gracefully', function (): void {
        $mockSite1 = createMockSiteData(['id' => 1, 'name' => 'site1.com']);

        $this->mock(ForgeClient::class, function ($mock) use ($mockSite1): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->with(1, 1)->once()->andReturn($mockSite1);
            $siteResource->shouldReceive('get')->with(1, 999)->once()->andThrow(new Exception('Site not found'));
            $siteResource->shouldReceive('deploy')->with(1, 1)->once();

            $mock->shouldReceive('sites')->andReturn($siteResource);
        });

        $response = ForgeServer::tool(BulkDeployTool::class, [
            'deployments' => [
                ['server_id' => 1, 'site_id' => 1],
                ['server_id' => 1, 'site_id' => 999],
            ],
        ]);

        $response
            ->assertOk()
            ->assertSee('successful')
            ->assertSee('failed');
    });
});

describe('SSLExpirationCheckTool', function (): void {
    it('checks SSL across all servers when no server_id provided', function (): void {
        $mockServer = createMockServerData(['id' => 1, 'name' => 'test-server']);
        $mockSite = createMockSiteData(['id' => 1, 'name' => 'example.com']);
        $mockCert = createMockCertificateData([
            'domain' => 'example.com',
            'expires_at' => now()->addDays(60)->toIso8601String(),
        ]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockServer, $mockSite, $mockCert): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('list')->once()->andReturn(
                ServerCollectionData::from(['servers' => [$mockServer->toArray()]])
            );

            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('list')->with(1)->once()->andReturn(
                SiteCollectionData::from(['sites' => [$mockSite->toArray()]])
            );

            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('list')->with(1, 1)->once()->andReturn(
                CertificateCollectionData::from(['certificates' => [$mockCert->toArray()]])
            );

            $mock->shouldReceive('servers')->andReturn($serverResource);
            $mock->shouldReceive('sites')->andReturn($siteResource);
            $mock->shouldReceive('certificates')->andReturn($certResource);
        });

        $response = ForgeServer::tool(SSLExpirationCheckTool::class, []);

        $response
            ->assertOk()
            ->assertSee('healthy')
            ->assertSee('example.com');
    });

    it('identifies expiring certificates', function (): void {
        $mockServer = createMockServerData(['id' => 1, 'name' => 'test-server']);
        $mockSite = createMockSiteData(['id' => 1, 'name' => 'example.com']);
        $mockCert = createMockCertificateData([
            'domain' => 'example.com',
            'expires_at' => now()->addDays(10)->toIso8601String(),
        ]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockServer, $mockSite, $mockCert): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('list')->once()->andReturn(
                ServerCollectionData::from(['servers' => [$mockServer->toArray()]])
            );

            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('list')->with(1)->once()->andReturn(
                SiteCollectionData::from(['sites' => [$mockSite->toArray()]])
            );

            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('list')->with(1, 1)->once()->andReturn(
                CertificateCollectionData::from(['certificates' => [$mockCert->toArray()]])
            );

            $mock->shouldReceive('servers')->andReturn($serverResource);
            $mock->shouldReceive('sites')->andReturn($siteResource);
            $mock->shouldReceive('certificates')->andReturn($certResource);
        });

        $response = ForgeServer::tool(SSLExpirationCheckTool::class, [
            'days_threshold' => 30,
        ]);

        $response
            ->assertOk()
            ->assertSee('expiring_soon')
            ->assertSee('action_required');
    });
});

describe('CloneSiteTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $this->mock(ForgeClient::class);

        $response = ForgeServer::tool(CloneSiteTool::class, []);

        $response->assertHasErrors();
    });

    it('clones site configuration successfully', function (): void {
        $mockSourceSite = createMockSiteData([
            'id' => 1,
            'name' => 'source.com',
            'repository' => 'user/repo',
            'repository_provider' => 'github',
            'repository_branch' => 'main',
        ]);

        $mockNewSite = createMockSiteData([
            'id' => 2,
            'name' => 'clone.com',
            'status' => 'installing',
        ]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockSourceSite, $mockNewSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->with(1, 1)->once()->andReturn($mockSourceSite);
            $siteResource->shouldReceive('create')->with(2, Mockery::any())->once()->andReturn($mockNewSite);
            $siteResource->shouldReceive('installGitRepository')->with(2, 2, Mockery::any())->once();
            $siteResource->shouldReceive('deploymentScript')->with(1, 1)->once()->andReturn('cd /home/forge/source.com && git pull');
            $siteResource->shouldReceive('updateDeploymentScript')->with(2, 2, Mockery::any())->once();

            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('list')->with(1, 1)->once()->andReturn(
                WorkerCollectionData::from(['workers' => []])
            );

            $jobResource = Mockery::mock(JobResource::class);
            $jobResource->shouldReceive('list')->with(1)->once()->andReturn(
                JobCollectionData::from(['jobs' => []])
            );

            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('obtainLetsEncrypt')->with(2, 2, Mockery::any())->once();

            $mock->shouldReceive('sites')->andReturn($siteResource);
            $mock->shouldReceive('workers')->andReturn($workerResource);
            $mock->shouldReceive('jobs')->andReturn($jobResource);
            $mock->shouldReceive('certificates')->andReturn($certResource);
        });

        $response = ForgeServer::tool(CloneSiteTool::class, [
            'source_server_id' => 1,
            'source_site_id' => 1,
            'target_server_id' => 2,
            'new_domain' => 'clone.com',
        ]);

        $response
            ->assertOk()
            ->assertSee('clone.com')
            ->assertSee('source.com');
    });
});

describe('Composite tools structure validation', function (): void {
    it('validates all composite tools exist', function (): void {
        $compositeToolsPath = app_path('Mcp/Tools/Composite');
        $toolFiles = collect(File::allFiles($compositeToolsPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Tool.php'))
            ->values();

        expect($toolFiles)->toHaveCount(5, 'Expected exactly 5 composite tools');
    });

    it('all composite tools can be instantiated', function (): void {
        $tools = [
            ServerHealthCheckTool::class,
            SiteStatusDashboardTool::class,
            BulkDeployTool::class,
            SSLExpirationCheckTool::class,
            CloneSiteTool::class,
        ];

        foreach ($tools as $toolClass) {
            $tool = app($toolClass);

            expect($tool->name())->toBeString()->not->toBeEmpty();
            expect($tool->description())->toBeString()->not->toBeEmpty();
        }
    });
});
