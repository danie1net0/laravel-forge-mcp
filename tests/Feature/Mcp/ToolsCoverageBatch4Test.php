<?php

declare(strict_types=1);

use App\Integrations\Forge\Data\Deployments\DeploymentData;
use App\Integrations\Forge\ForgeClient;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Sites\{ChangePhpVersionTool, CreateSiteTool, DeleteSiteTool, InstallPhpMyAdminTool, InstallWordPressTool, ListSitesTool, UninstallPhpMyAdminTool, UninstallWordPressTool, UpdateSiteTool};
use App\Mcp\Tools\Servers\{CreateServerTool, DeleteServerTool, GetEventOutputTool, ListEventsTool, RebootServerTool, UpdateDatabasePasswordTool};
use App\Mcp\Tools\Services\{InstallBlackfireTool, InstallPapertrailTool, RebootMysqlTool, RebootNginxTool, RebootPhpTool, RebootPostgresTool, RemoveBlackfireTool, RemovePapertrailTool, RestartServiceTool, StartServiceTool, StopMysqlTool, StopNginxTool, StopPostgresTool, StopServiceTool, TestNginxTool};
use App\Mcp\Tools\Composite\{BulkDeployTool, CloneSiteTool, SSLExpirationCheckTool, ServerHealthCheckTool, SiteStatusDashboardTool};
use App\Mcp\Tools\Integrations\{DisableHorizonTool, DisableInertiaTool, DisableMaintenanceTool, DisableOctaneTool, DisablePulseTool, DisableReverbTool, DisableSchedulerTool, EnableHorizonTool, EnableInertiaTool, EnableMaintenanceTool, EnableOctaneTool, EnablePulseTool, EnableReverbTool, EnableSchedulerTool, GetHorizonTool, GetInertiaTool, GetMaintenanceTool, GetOctaneTool, GetPulseTool, GetReverbTool, GetSchedulerTool};
use App\Integrations\Forge\Data\Jobs\{JobCollectionData, JobData};
use App\Integrations\Forge\Resources\{CertificateResource, DaemonResource, IntegrationResource, JobResource, MonitorResource, ServerResource, ServiceResource, SiteResource};
use App\Integrations\Forge\Data\Sites\{SiteCollectionData, SiteData};
use App\Integrations\Forge\Data\Daemons\{DaemonCollectionData};
use App\Integrations\Forge\Data\Servers\{ServerCollectionData, ServerData};
use App\Integrations\Forge\Data\Monitors\{MonitorCollectionData};
use App\Integrations\Forge\Data\Certificates\{CertificateCollectionData, CertificateData};
use App\Mcp\Tools\Certificates\InstallCertificateTool;
use App\Integrations\Forge\Data\Sites\{CreateSiteData, UpdateSiteData};
use App\Integrations\Forge\Data\Servers\CreateServerData;

beforeEach(function (): void {
    config([
        'services.forge.api_token' => 'test-token',
        'services.forge.organization' => 'test-org',
    ]);
});

function makeMockServerData(array $overrides = []): ServerData
{
    return ServerData::from(array_merge([
        'id' => 1,
        'credential_id' => 1,
        'name' => 'test-server',
        'type' => 'app',
        'provider' => 'ocean',
        'identifier' => 'test-1',
        'size' => '1gb',
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

function makeMockSiteData(array $overrides = []): SiteData
{
    return SiteData::from(array_merge([
        'id' => 1,
        'server_id' => 1,
        'name' => 'example.com',
        'status' => 'installed',
        'created_at' => '2024-01-01T00:00:00Z',
        'url' => 'http://example.com',
        'user' => 'forge',
        'https' => false,
        'web_directory' => '/home/forge/example.com/public',
        'root_directory' => '/home/forge/example.com',
        'aliases' => [],
        'php_version' => 'php84',
        'quick_deploy' => true,
        'wildcards' => false,
        'repository' => null,
        'app_type' => 'php',
        'tags' => [],
    ], $overrides));
}

function makeMockCertificateData(array $overrides = []): CertificateData
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

// ============================================================================
// INTEGRATIONS - Error path tests only (success + validation already covered)
// ============================================================================

describe('Integration tools error paths', function (): void {
    it('handles GetSchedulerTool validation errors', function (): void {
        $response = ForgeServer::tool(GetSchedulerTool::class, []);

        $response->assertHasErrors();
    });

    it('handles GetSchedulerTool success', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('getScheduler')->with(1, 1)->once()->andReturn([
                'enabled' => true,
                'status' => 'running',
            ]);
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(GetSchedulerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles GetSchedulerTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('getScheduler')->once()->andThrow(new Exception('Scheduler API Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(GetSchedulerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Scheduler API Error');
    });

    it('handles GetHorizonTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('getHorizon')->once()->andThrow(new Exception('Horizon API Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(GetHorizonTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Horizon API Error');
    });

    it('handles EnableHorizonTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enableHorizon')->once()->andThrow(new Exception('Enable Horizon Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnableHorizonTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Enable Horizon Error');
    });

    it('handles DisableHorizonTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('disableHorizon')->once()->andThrow(new Exception('Disable Horizon Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(DisableHorizonTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Disable Horizon Error');
    });

    it('handles GetOctaneTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('getOctane')->once()->andThrow(new Exception('Octane API Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(GetOctaneTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Octane API Error');
    });

    it('handles EnableOctaneTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enableOctane')->once()->andThrow(new Exception('Enable Octane Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnableOctaneTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'server' => 'swoole',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Enable Octane Error');
    });

    it('handles DisableOctaneTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('disableOctane')->once()->andThrow(new Exception('Disable Octane Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(DisableOctaneTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Disable Octane Error');
    });

    it('handles GetReverbTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('getReverb')->once()->andThrow(new Exception('Reverb API Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(GetReverbTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Reverb API Error');
    });

    it('handles EnableReverbTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enableReverb')->once()->andThrow(new Exception('Enable Reverb Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnableReverbTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Enable Reverb Error');
    });

    it('handles DisableReverbTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('disableReverb')->once()->andThrow(new Exception('Disable Reverb Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(DisableReverbTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Disable Reverb Error');
    });

    it('handles GetPulseTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('getPulse')->once()->andThrow(new Exception('Pulse API Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(GetPulseTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Pulse API Error');
    });

    it('handles EnablePulseTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enablePulse')->once()->andThrow(new Exception('Enable Pulse Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnablePulseTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Enable Pulse Error');
    });

    it('handles DisablePulseTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('disablePulse')->once()->andThrow(new Exception('Disable Pulse Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(DisablePulseTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Disable Pulse Error');
    });

    it('handles GetInertiaTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('getInertia')->once()->andThrow(new Exception('Inertia API Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(GetInertiaTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Inertia API Error');
    });

    it('handles EnableInertiaTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enableInertia')->once()->andThrow(new Exception('Enable Inertia Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnableInertiaTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Enable Inertia Error');
    });

    it('handles DisableInertiaTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('disableInertia')->once()->andThrow(new Exception('Disable Inertia Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(DisableInertiaTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Disable Inertia Error');
    });

    it('handles GetMaintenanceTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('getMaintenance')->once()->andThrow(new Exception('Maintenance API Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(GetMaintenanceTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Maintenance API Error');
    });

    it('handles EnableMaintenanceTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enableMaintenance')->once()->andThrow(new Exception('Enable Maintenance Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnableMaintenanceTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Enable Maintenance Error');
    });

    it('handles DisableMaintenanceTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('disableMaintenance')->once()->andThrow(new Exception('Disable Maintenance Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(DisableMaintenanceTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Disable Maintenance Error');
    });

    it('handles EnableSchedulerTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enableScheduler')->once()->andThrow(new Exception('Enable Scheduler Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnableSchedulerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Enable Scheduler Error');
    });

    it('handles DisableSchedulerTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('disableScheduler')->once()->andThrow(new Exception('Disable Scheduler Error'));
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(DisableSchedulerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Disable Scheduler Error');
    });

    it('handles EnableMaintenanceTool with optional parameters', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enableMaintenance')
                ->with(1, 1, 'my-secret', 500)
                ->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnableMaintenanceTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'secret' => 'my-secret',
            'status' => 500,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles EnableOctaneTool with custom port parameter', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enableOctane')
                ->with(1, 1, 'roadrunner', 9000)
                ->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnableOctaneTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'server' => 'roadrunner',
            'port' => 9000,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

// ============================================================================
// SERVERS - Error path tests (success + validation already covered)
// ============================================================================

describe('Server tools error paths', function (): void {
    it('handles CreateServerTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('create')->once()->andThrow(new Exception('Invalid credentials'));
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(CreateServerTool::class, [
            'name' => 'new-server',
            'provider' => 'ocean2',
            'type' => 'app',
            'ubuntu_version' => '24.04',
            'region_id' => 'nyc1',
            'size_id' => 's-1vcpu-1gb',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Invalid credentials');
    });

    it('handles DeleteServerTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('delete')->once()->andThrow(new Exception('Permission denied'));
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(DeleteServerTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Permission denied');
    });

    it('handles RebootServerTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('reboot')->once()->andThrow(new Exception('Reboot failed'));
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(RebootServerTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Reboot failed');
    });

    it('handles UpdateDatabasePasswordTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('updateDatabasePassword')->once()->andThrow(new Exception('Password update failed'));
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(UpdateDatabasePasswordTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Password update failed');
    });

    it('handles ListEventsTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('listEvents')->once()->andThrow(new Exception('Events not accessible'));
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListEventsTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Events not accessible');
    });

    it('handles GetEventOutputTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('getEventOutput')->once()->andThrow(new Exception('Event not found'));
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(GetEventOutputTool::class, [
            'server_id' => 1,
            'event_id' => 999,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Event not found');
    });
});

// ============================================================================
// SERVICES - Error path tests (success + validation already covered)
// ============================================================================

describe('Service tools error paths', function (): void {
    it('handles RebootNginxTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('rebootNginx')->once()->andThrow(new Exception('Nginx reboot failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RebootNginxTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Nginx reboot failed');
    });

    it('handles StopNginxTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('stopNginx')->once()->andThrow(new Exception('Nginx stop failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(StopNginxTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Nginx stop failed');
    });

    it('handles TestNginxTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('testNginx')->once()->andThrow(new Exception('Nginx test failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(TestNginxTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Nginx test failed');
    });

    it('handles RebootMysqlTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('rebootMysql')->once()->andThrow(new Exception('MySQL reboot failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RebootMysqlTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('MySQL reboot failed');
    });

    it('handles StopMysqlTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('stopMysql')->once()->andThrow(new Exception('MySQL stop failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(StopMysqlTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('MySQL stop failed');
    });

    it('handles RebootPostgresTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('rebootPostgres')->once()->andThrow(new Exception('Postgres reboot failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RebootPostgresTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Postgres reboot failed');
    });

    it('handles StopPostgresTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('stopPostgres')->once()->andThrow(new Exception('Postgres stop failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(StopPostgresTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Postgres stop failed');
    });

    it('handles RebootPhpTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('rebootPhp')->once()->andThrow(new Exception('PHP reboot failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RebootPhpTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('PHP reboot failed');
    });

    it('handles InstallBlackfireTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('installBlackfire')->once()->andThrow(new Exception('Blackfire install failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(InstallBlackfireTool::class, [
            'server_id' => 1,
            'server_id_token' => 'server-id',
            'server_token' => 'server-token',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Blackfire install failed');
    });

    it('handles RemoveBlackfireTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('removeBlackfire')->once()->andThrow(new Exception('Blackfire remove failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RemoveBlackfireTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Blackfire remove failed');
    });

    it('handles InstallPapertrailTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('installPapertrail')->once()->andThrow(new Exception('Papertrail install failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(InstallPapertrailTool::class, [
            'server_id' => 1,
            'host' => 'logs.papertrailapp.com:12345',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Papertrail install failed');
    });

    it('handles RemovePapertrailTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('removePapertrail')->once()->andThrow(new Exception('Papertrail remove failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RemovePapertrailTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Papertrail remove failed');
    });

    it('handles StartServiceTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('startService')->once()->andThrow(new Exception('Service start failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(StartServiceTool::class, [
            'server_id' => 1,
            'service' => 'nginx',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Service start failed');
    });

    it('handles StopServiceTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('stopService')->once()->andThrow(new Exception('Service stop failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(StopServiceTool::class, [
            'server_id' => 1,
            'service' => 'nginx',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Service stop failed');
    });

    it('handles RestartServiceTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('restartService')->once()->andThrow(new Exception('Service restart failed'));
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RestartServiceTool::class, [
            'server_id' => 1,
            'service' => 'nginx',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Service restart failed');
    });
});

// ============================================================================
// SITES - Error path tests (success + validation already covered)
// ============================================================================

describe('Site tools error paths', function (): void {
    it('handles ListSitesTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('list')->once()->andThrow(new Exception('Server not found'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ListSitesTool::class, ['server_id' => 999]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Server not found');
    });

    it('handles CreateSiteTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('create')->once()->andThrow(new Exception('Domain already exists'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(CreateSiteTool::class, [
            'server_id' => 1,
            'domain' => 'example.com',
            'project_type' => 'php',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Domain already exists');
    });

    it('handles UpdateSiteTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('update')->once()->andThrow(new Exception('Site update failed'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UpdateSiteTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'directory' => '/public_html',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Site update failed');
    });

    it('handles DeleteSiteTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('delete')->once()->andThrow(new Exception('Site not found'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(DeleteSiteTool::class, [
            'server_id' => 1,
            'site_id' => 999,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Site not found');
    });

    it('handles ChangePhpVersionTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('changePhpVersion')->once()->andThrow(new Exception('PHP version change failed'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ChangePhpVersionTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'version' => 'php84',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('PHP version change failed');
    });

    it('handles InstallWordPressTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('installWordPress')->once()->andThrow(new Exception('WordPress install failed'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(InstallWordPressTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'database' => 'wordpress',
            'user' => 'admin',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('WordPress install failed');
    });

    it('handles UninstallWordPressTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('uninstallWordPress')->once()->andThrow(new Exception('WordPress uninstall failed'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UninstallWordPressTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('WordPress uninstall failed');
    });

    it('handles InstallPhpMyAdminTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('installPhpMyAdmin')->once()->andThrow(new Exception('phpMyAdmin install failed'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(InstallPhpMyAdminTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('phpMyAdmin install failed');
    });

    it('handles UninstallPhpMyAdminTool API errors', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('uninstallPhpMyAdmin')->once()->andThrow(new Exception('phpMyAdmin uninstall failed'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UninstallPhpMyAdminTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('phpMyAdmin uninstall failed');
    });
});

// ============================================================================
// COMPOSITE - Error paths + uncovered branches
// ============================================================================

describe('ServerHealthCheckTool error paths', function (): void {
    it('handles API error on server get', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('get')->once()->andThrow(new Exception('Server not found'));
            $mock->shouldReceive('servers')->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ServerHealthCheckTool::class, ['server_id' => 999]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Server not found');
    });

    it('reports critical status when server is not ready', function (): void {
        $mockServer = makeMockServerData(['is_ready' => false]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockServer): void {
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

        $response = ForgeServer::tool(ServerHealthCheckTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('critical')->assertSee('Server is not ready');
    });

    it('reports warning when sites exist but none are installed', function (): void {
        $mockServer = makeMockServerData();
        $mockSite = makeMockSiteData(['status' => 'installing']);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockServer, $mockSite): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('get')->with(1)->once()->andReturn($mockServer);
            $serverResource->shouldReceive('listEvents')->with(1)->once()->andReturn([]);

            $monitorResource = Mockery::mock(MonitorResource::class);
            $monitorResource->shouldReceive('list')->with(1)->once()->andReturn(
                MonitorCollectionData::from(['monitors' => [
                    [
                        'id' => 1,
                        'server_id' => 1,
                        'status' => 'active',
                        'type' => 'disk',
                        'operator' => '>',
                        'threshold' => 80,
                        'minutes' => 5,
                        'state' => 'OK',
                        'state_changed_at' => '2024-01-01T00:00:00Z',
                    ],
                ]])
            );

            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('list')->with(1)->once()->andReturn(
                SiteCollectionData::from(['sites' => [$mockSite->toArray()]])
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

        $response = ForgeServer::tool(ServerHealthCheckTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('warning')->assertSee('Some sites are not fully installed');
    });

    it('handles inner exception for monitors gracefully', function (): void {
        $mockServer = makeMockServerData();

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockServer): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('get')->with(1)->once()->andReturn($mockServer);
            $serverResource->shouldReceive('listEvents')->with(1)->once()->andThrow(new Exception('Events error'));

            $monitorResource = Mockery::mock(MonitorResource::class);
            $monitorResource->shouldReceive('list')->with(1)->once()->andThrow(new Exception('Monitors error'));

            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('list')->with(1)->once()->andThrow(new Exception('Sites error'));

            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('list')->with(1)->once()->andThrow(new Exception('Daemons error'));

            $mock->shouldReceive('servers')->andReturn($serverResource);
            $mock->shouldReceive('monitors')->andReturn($monitorResource);
            $mock->shouldReceive('sites')->andReturn($siteResource);
            $mock->shouldReceive('daemons')->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(ServerHealthCheckTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true')->assertSee('No monitors configured');
    });
});

describe('SiteStatusDashboardTool error paths', function (): void {
    it('handles API error on site get', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->once()->andThrow(new Exception('Site not found'));
            $mock->shouldReceive('sites')->andReturn($siteResource);
        });

        $response = ForgeServer::tool(SiteStatusDashboardTool::class, [
            'server_id' => 1,
            'site_id' => 999,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Site not found');
    });

    it('handles inner exceptions for sub-resources gracefully', function (): void {
        $mockSite = makeMockSiteData([
            'repository' => ['provider' => 'github', 'url' => 'user/repo', 'branch' => 'main', 'status' => 'installed'],
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->with(1, 1)->once()->andReturn($mockSite);
            $siteResource->shouldReceive('deploymentHistory')->with(1, 1)->once()->andThrow(new Exception('Deployments error'));

            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('list')->with(1, 1)->once()->andThrow(new Exception('Certificates error'));

            $jobResource = Mockery::mock(JobResource::class);
            $jobResource->shouldReceive('list')->with(1)->once()->andThrow(new Exception('Jobs error'));

            $mock->shouldReceive('sites')->andReturn($siteResource);
            $mock->shouldReceive('certificates')->andReturn($certResource);
            $mock->shouldReceive('jobs')->andReturn($jobResource);
        });

        $response = ForgeServer::tool(SiteStatusDashboardTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true')->assertSee('example.com');
    });

    it('displays active certificate and deployment info', function (): void {
        $mockSite = makeMockSiteData([
            'repository' => ['provider' => 'github', 'url' => 'user/repo', 'branch' => 'main', 'status' => 'installed'],
        ]);

        $mockCert = makeMockCertificateData([
            'active' => true,
            'expires_at' => '2025-06-01T00:00:00Z',
        ]);

        $mockDeployment = DeploymentData::from([
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'type' => 0,
            'commit_hash' => 'abc123',
            'commit_author' => 'Author',
            'commit_message' => 'Deploy message',
            'status' => 'finished',
            'started_at' => '2024-01-01T00:00:00Z',
            'ended_at' => '2024-01-01T00:01:00Z',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $mockJob = JobData::from([
            'id' => 1,
            'server_id' => 1,
            'command' => 'cd /home/forge/example.com && php artisan schedule:run',
            'user' => 'forge',
            'frequency' => 'minutely',
            'cron' => '* * * * *',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockSite, $mockCert, $mockDeployment, $mockJob): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->with(1, 1)->once()->andReturn($mockSite);
            $siteResource->shouldReceive('deploymentHistory')->with(1, 1)->once()->andReturn([$mockDeployment]);

            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('list')->with(1, 1)->once()->andReturn(
                CertificateCollectionData::from(['certificates' => [$mockCert->toArray()]])
            );

            $jobResource = Mockery::mock(JobResource::class);
            $jobResource->shouldReceive('list')->with(1)->once()->andReturn(
                new JobCollectionData(jobs: [$mockJob])
            );

            $mock->shouldReceive('sites')->andReturn($siteResource);
            $mock->shouldReceive('certificates')->andReturn($certResource);
            $mock->shouldReceive('jobs')->andReturn($jobResource);
        });

        $response = ForgeServer::tool(SiteStatusDashboardTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('active')
            ->assertSee('finished');
    });
});

describe('BulkDeployTool error paths', function (): void {
    it('handles all deployments failing', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->with(1, 1)->once()->andThrow(new Exception('Site 1 not found'));
            $siteResource->shouldReceive('get')->with(1, 2)->once()->andThrow(new Exception('Site 2 not found'));

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
            ->assertSee('"success": false')
            ->assertSee('"failed": 2')
            ->assertSee('"successful": 0');
    });
});

describe('SSLExpirationCheckTool error paths', function (): void {
    it('handles outer API error gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('list')->once()->andThrow(new Exception('API unavailable'));
            $mock->shouldReceive('servers')->andReturn($serverResource);
        });

        $response = ForgeServer::tool(SSLExpirationCheckTool::class, []);

        $response->assertOk()->assertSee('"success": false')->assertSee('API unavailable');
    });

    it('checks specific server when server_id provided', function (): void {
        $mockServer = makeMockServerData(['id' => 5]);
        $mockSite = makeMockSiteData(['id' => 1]);
        $mockCert = makeMockCertificateData([
            'active' => true,
            'expires_at' => now()->addDays(60)->toIso8601String(),
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockServer, $mockSite, $mockCert): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('get')->with(5)->once()->andReturn($mockServer);

            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('list')->with(5)->once()->andReturn(
                SiteCollectionData::from(['sites' => [$mockSite->toArray()]])
            );

            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('list')->with(5, 1)->once()->andReturn(
                CertificateCollectionData::from(['certificates' => [$mockCert->toArray()]])
            );

            $mock->shouldReceive('servers')->andReturn($serverResource);
            $mock->shouldReceive('sites')->andReturn($siteResource);
            $mock->shouldReceive('certificates')->andReturn($certResource);
        });

        $response = ForgeServer::tool(SSLExpirationCheckTool::class, ['server_id' => 5]);

        $response->assertOk()->assertSee('"success": true')->assertSee('healthy');
    });

    it('detects expired certificates', function (): void {
        $mockServer = makeMockServerData();
        $mockSite = makeMockSiteData();
        $mockCert = makeMockCertificateData([
            'active' => true,
            'expires_at' => now()->subDays(5)->toIso8601String(),
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockServer, $mockSite, $mockCert): void {
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

        $response->assertOk()->assertSee('"action_required": true')->assertSee('"expired": 1');
    });

    it('skips inactive certificates', function (): void {
        $mockServer = makeMockServerData();
        $mockSite = makeMockSiteData();
        $mockCert = makeMockCertificateData([
            'active' => false,
            'expires_at' => now()->subDays(5)->toIso8601String(),
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockServer, $mockSite, $mockCert): void {
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

        $response->assertOk()->assertSee('"total_checked": 0');
    });

    it('handles certificate with null expires_at as healthy', function (): void {
        $mockServer = makeMockServerData();
        $mockSite = makeMockSiteData();
        $mockCert = makeMockCertificateData([
            'active' => true,
            'expires_at' => null,
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockServer, $mockSite, $mockCert): void {
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

        $response->assertOk()->assertSee('"healthy": 1');
    });

    it('handles inner site list error', function (): void {
        $mockServer = makeMockServerData();

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockServer): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('list')->once()->andReturn(
                ServerCollectionData::from(['servers' => [$mockServer->toArray()]])
            );

            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('list')->with(1)->once()->andThrow(new Exception('Sites error'));

            $mock->shouldReceive('servers')->andReturn($serverResource);
            $mock->shouldReceive('sites')->andReturn($siteResource);
        });

        $response = ForgeServer::tool(SSLExpirationCheckTool::class, []);

        $response->assertOk()->assertSee('"errors": 1')->assertSee('Sites error');
    });

    it('handles inner certificate list error', function (): void {
        $mockServer = makeMockServerData();
        $mockSite = makeMockSiteData();

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockServer, $mockSite): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('list')->once()->andReturn(
                ServerCollectionData::from(['servers' => [$mockServer->toArray()]])
            );

            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('list')->with(1)->once()->andReturn(
                SiteCollectionData::from(['sites' => [$mockSite->toArray()]])
            );

            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('list')->with(1, 1)->once()->andThrow(new Exception('Certificates error'));

            $mock->shouldReceive('servers')->andReturn($serverResource);
            $mock->shouldReceive('sites')->andReturn($siteResource);
            $mock->shouldReceive('certificates')->andReturn($certResource);
        });

        $response = ForgeServer::tool(SSLExpirationCheckTool::class, []);

        $response->assertOk()->assertSee('"errors": 1')->assertSee('Certificates error');
    });
});

describe('CloneSiteTool error paths', function (): void {
    it('handles source site not found', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->with(1, 1)->once()->andThrow(new Exception('Source site not found'));
            $mock->shouldReceive('sites')->andReturn($siteResource);
        });

        $response = ForgeServer::tool(CloneSiteTool::class, [
            'source_server_id' => 1,
            'source_site_id' => 1,
            'target_server_id' => 2,
            'new_domain' => 'clone.com',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Source site not found');
    });

    it('clones site without workers, jobs, and ssl when disabled', function (): void {
        $mockSourceSite = makeMockSiteData([
            'id' => 1,
            'name' => 'source.com',
            'repository' => null,
        ]);

        $mockNewSite = makeMockSiteData([
            'id' => 2,
            'name' => 'clone.com',
            'status' => 'installing',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockSourceSite, $mockNewSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->with(1, 1)->once()->andReturn($mockSourceSite);
            $siteResource->shouldReceive('create')->with(2, Mockery::any())->once()->andReturn($mockNewSite);
            $siteResource->shouldReceive('deploymentScript')->with(1, 1)->once()->andReturn('cd /home/forge/source.com && git pull');
            $siteResource->shouldReceive('updateDeploymentScript')->with(2, 2, Mockery::any())->once();

            $mock->shouldReceive('sites')->andReturn($siteResource);
        });

        $response = ForgeServer::tool(CloneSiteTool::class, [
            'source_server_id' => 1,
            'source_site_id' => 1,
            'target_server_id' => 2,
            'new_domain' => 'clone.com',
            'clone_workers' => false,
            'clone_jobs' => false,
            'clone_ssl' => false,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('clone.com')
            ->assertSee('source.com');
    });

    it('handles git repository install failure gracefully', function (): void {
        $mockSourceSite = makeMockSiteData([
            'id' => 1,
            'name' => 'source.com',
            'repository' => ['provider' => 'github', 'url' => 'user/repo', 'branch' => 'main', 'status' => 'installed'],
        ]);

        $mockNewSite = makeMockSiteData([
            'id' => 2,
            'name' => 'clone.com',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockSourceSite, $mockNewSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->with(1, 1)->once()->andReturn($mockSourceSite);
            $siteResource->shouldReceive('create')->with(2, Mockery::any())->once()->andReturn($mockNewSite);
            $siteResource->shouldReceive('installGitRepository')->once()->andThrow(new Exception('Git install failed'));
            $siteResource->shouldReceive('deploymentScript')->once()->andThrow(new Exception('Script fetch failed'));

            $jobResource = Mockery::mock(JobResource::class);
            $jobResource->shouldReceive('list')->once()->andThrow(new Exception('Jobs fetch failed'));

            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('list')->once()->andThrow(new Exception('SSL failed'));

            $mock->shouldReceive('sites')->andReturn($siteResource);
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
            ->assertSee('"success": false')
            ->assertSee('Git install failed')
            ->assertSee('Script fetch failed')
            ->assertSee('Jobs fetch failed')
            ->assertSee('SSL failed');
    });

    it('clones jobs from source site', function (): void {
        $mockSourceSite = makeMockSiteData([
            'id' => 1,
            'name' => 'source.com',
            'repository' => null,
        ]);

        $mockNewSite = makeMockSiteData([
            'id' => 2,
            'name' => 'clone.com',
        ]);

        $mockJob = JobData::from([
            'id' => 1,
            'server_id' => 1,
            'command' => 'cd /home/forge/source.com && php artisan schedule:run',
            'user' => 'forge',
            'frequency' => 'minutely',
            'cron' => '* * * * *',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockSourceSite, $mockNewSite, $mockJob): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->with(1, 1)->once()->andReturn($mockSourceSite);
            $siteResource->shouldReceive('create')->with(2, Mockery::any())->once()->andReturn($mockNewSite);
            $siteResource->shouldReceive('deploymentScript')->with(1, 1)->once()->andReturn('cd /home/forge/source.com');
            $siteResource->shouldReceive('updateDeploymentScript')->with(2, 2, 'cd /home/forge/clone.com')->once();

            $jobResource = Mockery::mock(JobResource::class);
            $jobResource->shouldReceive('list')->with(1)->once()->andReturn(
                new JobCollectionData(jobs: [$mockJob])
            );
            $jobResource->shouldReceive('create')->with(2, Mockery::any())->once();

            $mockDomainCert = makeMockCertificateData(['id' => 10, 'domain' => 'clone.com']);
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('list')->with(2, 2)->once()->andReturn(
                CertificateCollectionData::from(['certificates' => [$mockDomainCert->toArray()]])
            );
            $certResource->shouldReceive('obtainLetsEncrypt')->with(2, 2, 10)->once();

            $mock->shouldReceive('sites')->andReturn($siteResource);
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
            ->assertSee('"success": true')
            ->assertSee('Cloned 1 scheduled jobs');
    });
});

// ============================================================================
// COVERAGE BATCH 5 - Uncovered optional parameter branches
// ============================================================================

describe('InstallCertificateTool domain-based parameters', function (): void {
    it('accepts domain_id parameter', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $certificateResource = Mockery::mock(CertificateResource::class);
            $certificateResource->shouldReceive('activate')
                ->with(1, 1, 1)
                ->once();
            $mock->shouldReceive('certificates')->andReturn($certificateResource);
        });

        $response = ForgeServer::tool(InstallCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'domain_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('CreateServerTool optional parameters', function (): void {
    it('accepts optional fields like credential_id, database, tags and php_version', function (): void {
        $mockServer = makeMockServerData([
            'id' => 5,
            'name' => 'db-server',
            'size' => 's-2vcpu-2gb',
            'region' => 'nyc3',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockServer): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('create')
                ->with(Mockery::type(CreateServerData::class))
                ->once()
                ->andReturn($mockServer);
            $mock->shouldReceive('servers')->andReturn($serverResource);
        });

        $response = ForgeServer::tool(CreateServerTool::class, [
            'name' => 'db-server',
            'provider' => 'ocean2',
            'type' => 'database',
            'ubuntu_version' => '24.04',
            'region_id' => 'nyc3',
            'size_id' => 's-2vcpu-2gb',
            'credential_id' => 1,
            'php_version' => 'php83',
            'database_type' => 'mysql8',
            'database' => 'forge_db',
            'tags' => ['production', 'database'],
            'add_key_to_source_control' => false,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('db-server');
    });

    it('creates server with custom provider using ip_address', function (): void {
        $mockServer = makeMockServerData([
            'id' => 6,
            'name' => 'custom-server',
            'provider' => 'custom',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockServer): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('create')
                ->with(Mockery::type(CreateServerData::class))
                ->once()
                ->andReturn($mockServer);
            $mock->shouldReceive('servers')->andReturn($serverResource);
        });

        $response = ForgeServer::tool(CreateServerTool::class, [
            'name' => 'custom-server',
            'provider' => 'custom',
            'type' => 'app',
            'ubuntu_version' => '22.04',
            'ip_address' => '203.0.113.10',
            'private_ip_address' => '10.0.0.5',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('custom-server');
    });
});

describe('CreateSiteTool optional parameters', function (): void {
    it('accepts all optional parameters', function (): void {
        $mockSite = makeMockSiteData([
            'id' => 10,
            'name' => 'full-site.com',
            'root_directory' => '/home/forge/full-site.com',
            'web_directory' => '/home/forge/full-site.com/public',
            'aliases' => ['www.full-site.com', 'blog.full-site.com'],
            'user' => 'siteuser',
            'php_version' => 'php83',
            'status' => 'installing',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('create')
                ->with(1, Mockery::type(CreateSiteData::class))
                ->once()
                ->andReturn($mockSite);
            $mock->shouldReceive('sites')->andReturn($siteResource);
        });

        $response = ForgeServer::tool(CreateSiteTool::class, [
            'server_id' => 1,
            'domain' => 'full-site.com',
            'project_type' => 'php',
            'aliases' => ['www.full-site.com', 'blog.full-site.com'],
            'directory' => '/public',
            'isolated' => true,
            'username' => 'siteuser',
            'database' => 'full_site_db',
            'php_version' => 'php83',
            'nginx_template' => 5,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('full-site.com');
    });
});

describe('UpdateSiteTool optional parameters', function (): void {
    it('accepts aliases and isolated parameters', function (): void {
        $mockSite = makeMockSiteData([
            'aliases' => ['www.example.com', 'api.example.com'],
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('update')
                ->with(1, 1, Mockery::type(UpdateSiteData::class))
                ->once()
                ->andReturn($mockSite);
            $mock->shouldReceive('sites')->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UpdateSiteTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'aliases' => ['www.example.com', 'api.example.com'],
            'isolated' => true,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('www.example.com');
    });
});
