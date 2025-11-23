<?php

declare(strict_types=1);

use App\Mcp\Prompts\DeployApplicationPrompt;
use App\Mcp\Resources\{DeploymentGuidelinesResource, ForgeApiDocsResource};
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Certificates\{GetCertificateTool, ListCertificatesTool, ObtainLetsEncryptCertificateTool};
use App\Mcp\Tools\Daemons\{GetDaemonTool, ListDaemonsTool};
use App\Mcp\Tools\Databases\{GetDatabaseTool, ListDatabasesTool};
use App\Mcp\Tools\Deployments\{DeploySiteTool, GetDeploymentLogTool, GetDeploymentScriptTool};
use App\Mcp\Tools\Firewall\{GetFirewallRuleTool, ListFirewallRulesTool};
use App\Mcp\Tools\Jobs\{GetScheduledJobTool, ListScheduledJobsTool};
use App\Mcp\Tools\Servers\{GetServerTool, ListServersTool, RebootServerTool};
use App\Mcp\Tools\Sites\{GetSiteTool, ListSitesTool};
use App\Services\ForgeService;
use Laravel\Forge\Resources\{Certificate, Daemon, Database, FirewallRule, Job, Server, Site};

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

describe('ForgeServer', function (): void {
    it('has registered tools', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('listServers')->once()->andReturn([]);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response->assertOk();
    });
});

describe('ListServersTool', function (): void {
    it('lists servers successfully', function (): void {
        $mockServer = new Server([
            'id' => 1,
            'name' => 'test-server',
            'type' => 'app',
            'ipAddress' => '192.168.1.1',
            'region' => 'nyc1',
            'size' => '1gb',
            'phpVersion' => '8.2',
            'isReady' => true,
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockServer): void {
            $mock->shouldReceive('listServers')->once()->andReturn([$mockServer]);
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
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('listServers')->once()->andReturn([]);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response
            ->assertOk()
            ->assertSee('"count": 0')
            ->assertSee('"servers": []');
    });

    it('lists multiple servers', function (): void {
        $servers = [
            new Server(['id' => 1, 'name' => 'server-1', 'type' => 'app', 'ipAddress' => '1.1.1.1', 'isReady' => true]),
            new Server(['id' => 2, 'name' => 'server-2', 'type' => 'web', 'ipAddress' => '2.2.2.2', 'isReady' => false]),
            new Server(['id' => 3, 'name' => 'server-3', 'type' => 'database', 'ipAddress' => '3.3.3.3', 'isReady' => true]),
        ];

        $this->mock(ForgeService::class, function ($mock) use ($servers): void {
            $mock->shouldReceive('listServers')->once()->andReturn($servers);
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
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('listServers')->once()->andThrow(new Exception('API Error'));
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('API Error');
    });

    it('handles network timeout errors', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('listServers')->once()->andThrow(new Exception('Connection timed out'));
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response
            ->assertOk()
            ->assertSee('Connection timed out');
    });

    it('handles authentication errors', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('listServers')->once()->andThrow(new Exception('Unauthorized: Invalid API token'));
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
        $mockServer = new Server([
            'id' => 1,
            'name' => 'test-server',
            'type' => 'app',
            'ipAddress' => '192.168.1.1',
            'privateIpAddress' => '10.0.0.1',
            'region' => 'nyc1',
            'size' => '1gb',
            'phpVersion' => '8.2',
            'isReady' => true,
            'revoked' => false,
            'network' => [],
            'tags' => [],
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockServer): void {
            $mock->shouldReceive('getServer')->with(1)->once()->andReturn($mockServer);
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
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('getServer')->with(999)->once()->andThrow(new Exception('Server not found'));
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
        $mockServer = new Server([
            'id' => 1,
            'name' => 'networked-server',
            'type' => 'app',
            'ipAddress' => '192.168.1.1',
            'isReady' => true,
            'revoked' => false,
            'network' => [2, 3, 4],
            'tags' => [['id' => 1, 'name' => 'production']],
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockServer): void {
            $mock->shouldReceive('getServer')->with(1)->once()->andReturn($mockServer);
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
        $mockSite = new Site([
            'id' => 1,
            'name' => 'example.com',
            'directory' => '/home/forge/example.com',
            'repository' => 'git@github.com:example/repo.git',
            'repositoryBranch' => 'main',
            'repositoryStatus' => 'installed',
            'quickDeploy' => true,
            'projectType' => 'php',
            'phpVersion' => '8.2',
            'isSecured' => true,
            'status' => 'installed',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockSite): void {
            $mock->shouldReceive('listSites')->with(1)->once()->andReturn([$mockSite]);
        });

        $response = ForgeServer::tool(ListSitesTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('example.com')
            ->assertSee('main')
            ->assertSee('php');
    });

    it('returns empty list when no sites exist', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('listSites')->with(1)->once()->andReturn([]);
        });

        $response = ForgeServer::tool(ListSitesTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"count": 0');
    });

    it('lists multiple sites', function (): void {
        $sites = [
            new Site(['id' => 1, 'name' => 'site1.com', 'directory' => '/home/forge/site1.com']),
            new Site(['id' => 2, 'name' => 'site2.com', 'directory' => '/home/forge/site2.com']),
        ];

        $this->mock(ForgeService::class, function ($mock) use ($sites): void {
            $mock->shouldReceive('listSites')->with(1)->once()->andReturn($sites);
        });

        $response = ForgeServer::tool(ListSitesTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"count": 2')
            ->assertSee('site1.com')
            ->assertSee('site2.com');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('listSites')->with(1)->once()->andThrow(new Exception('Server not found'));
        });

        $response = ForgeServer::tool(ListSitesTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Server not found');
    });
});

describe('GetSiteTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetSiteTool::class, []);

        $response->assertHasErrors();
    });

    it('gets site details successfully', function (): void {
        $mockSite = new Site([
            'id' => 1,
            'name' => 'example.com',
            'directory' => '/home/forge/example.com',
            'repository' => 'git@github.com:example/repo.git',
            'repositoryBranch' => 'main',
            'repositoryProvider' => 'github',
            'repositoryStatus' => 'installed',
            'quickDeploy' => true,
            'projectType' => 'php',
            'phpVersion' => '8.2',
            'app' => null,
            'appStatus' => null,
            'isSecured' => true,
            'status' => 'installed',
            'deploymentStatus' => null,
            'tags' => [],
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockSite): void {
            $mock->shouldReceive('getSite')->with(1, 1)->once()->andReturn($mockSite);
        });

        $response = ForgeServer::tool(GetSiteTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('example.com');
    });
});

describe('DeploySiteTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(DeploySiteTool::class, []);

        $response->assertHasErrors();
    });

    it('requires site_id when server_id is provided', function (): void {
        $response = ForgeServer::tool(DeploySiteTool::class, [
            'server_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('deploys site successfully', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('deploySite')->with(1, 1)->once();
        });

        $response = ForgeServer::tool(DeploySiteTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('Deployment triggered successfully');
    });

    it('handles deployment errors', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('deploySite')->with(1, 1)->once()->andThrow(new Exception('Deployment already in progress'));
        });

        $response = ForgeServer::tool(DeploySiteTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Deployment already in progress');
    });

    it('handles site not found error', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('deploySite')->with(1, 999)->once()->andThrow(new Exception('Site not found'));
        });

        $response = ForgeServer::tool(DeploySiteTool::class, [
            'server_id' => 1,
            'site_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('Site not found');
    });
});

describe('GetDeploymentLogTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetDeploymentLogTool::class, []);

        $response->assertHasErrors();
    });

    it('gets deployment log successfully', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('siteDeploymentLog')
                ->with(1, 1)
                ->once()
                ->andReturn('Deployment completed successfully');
        });

        $response = ForgeServer::tool(GetDeploymentLogTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('Deployment completed successfully');
    });

    it('handles empty deployment log', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('siteDeploymentLog')
                ->with(1, 1)
                ->once()
                ->andReturn(null);
        });

        $response = ForgeServer::tool(GetDeploymentLogTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('"log": null');
    });

    it('handles API errors', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('siteDeploymentLog')
                ->with(1, 1)
                ->once()
                ->andThrow(new Exception('Failed to fetch log'));
        });

        $response = ForgeServer::tool(GetDeploymentLogTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Failed to fetch log');
    });
});

describe('GetDeploymentScriptTool', function (): void {
    it('gets deployment script successfully', function (): void {
        $deploymentScript = 'cd /home/forge/example.com && git pull origin main';

        $this->mock(ForgeService::class, function ($mock) use ($deploymentScript): void {
            $mock->shouldReceive('getSiteDeploymentScript')
                ->with(1, 1)
                ->once()
                ->andReturn($deploymentScript);
        });

        $response = ForgeServer::tool(GetDeploymentScriptTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('git pull origin main');
    });
});

describe('Resources', function (): void {
    it('returns forge api docs resource', function (): void {
        $response = ForgeServer::resource(ForgeApiDocsResource::class);

        $response
            ->assertOk()
            ->assertSee('Laravel Forge API Documentation')
            ->assertSee('Authentication');
    });

    it('returns deployment guidelines resource', function (): void {
        $response = ForgeServer::resource(DeploymentGuidelinesResource::class);

        $response
            ->assertOk()
            ->assertSee('Deployment Guidelines')
            ->assertSee('Pre-Deployment Checklist');
    });
});

describe('Prompts', function (): void {
    it('returns deploy application prompt', function (): void {
        $response = ForgeServer::prompt(DeployApplicationPrompt::class, [
            'server_id' => '123',
            'site_id' => '456',
        ]);

        $response
            ->assertOk()
            ->assertSee('Deployment Guide')
            ->assertSee('123')
            ->assertSee('456');
    });
});

describe('ListCertificatesTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(ListCertificatesTool::class, []);

        $response->assertHasErrors();
    });

    it('lists certificates successfully', function (): void {
        $mockCert = new Certificate([
            'id' => 1,
            'domain' => 'example.com',
            'type' => 'letsencrypt',
            'status' => 'installed',
            'active' => true,
            'existing' => false,
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockCert): void {
            $mock->shouldReceive('listCertificates')->with(1, 1)->once()->andReturn([$mockCert]);
        });

        $response = ForgeServer::tool(ListCertificatesTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('example.com')
            ->assertSee('letsencrypt');
    });
});

describe('GetCertificateTool', function (): void {
    it('requires server_id, site_id and certificate_id parameters', function (): void {
        $response = ForgeServer::tool(GetCertificateTool::class, []);

        $response->assertHasErrors();
    });

    it('requires certificate_id when server_id and site_id provided', function (): void {
        $response = ForgeServer::tool(GetCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects invalid parameter types', function (): void {
        $response = ForgeServer::tool(GetCertificateTool::class, [
            'server_id' => 'invalid',
            'site_id' => 1,
            'certificate_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects negative parameters', function (): void {
        $response = ForgeServer::tool(GetCertificateTool::class, [
            'server_id' => 1,
            'site_id' => -1,
            'certificate_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('gets certificate details successfully', function (): void {
        $mockCert = new Certificate([
            'id' => 1,
            'domain' => 'example.com',
            'type' => 'letsencrypt',
            'status' => 'installed',
            'active' => true,
            'existing' => false,
            'requestStatus' => 'created',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockCert): void {
            $mock->shouldReceive('getCertificate')->with(1, 1, 1)->once()->andReturn($mockCert);
        });

        $response = ForgeServer::tool(GetCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'certificate_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('example.com')
            ->assertSee('letsencrypt')
            ->assertSee('installed');
    });

    it('handles certificate not found error', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('getCertificate')->with(1, 1, 999)->once()->andThrow(new Exception('Certificate not found'));
        });

        $response = ForgeServer::tool(GetCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'certificate_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Certificate not found');
    });

    it('returns custom certificate details', function (): void {
        $mockCert = new Certificate([
            'id' => 2,
            'domain' => 'custom.example.com',
            'type' => 'custom',
            'status' => 'installed',
            'active' => true,
            'existing' => true,
            'requestStatus' => null,
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockCert): void {
            $mock->shouldReceive('getCertificate')->with(1, 1, 2)->once()->andReturn($mockCert);
        });

        $response = ForgeServer::tool(GetCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'certificate_id' => 2,
        ]);

        $response
            ->assertOk()
            ->assertSee('custom.example.com')
            ->assertSee('"type": "custom"')
            ->assertSee('"existing": true');
    });
});

describe('ListDatabasesTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListDatabasesTool::class, []);

        $response->assertHasErrors();
    });

    it('lists databases successfully', function (): void {
        $mockDb = new Database([
            'id' => 1,
            'name' => 'forge',
            'status' => 'installed',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockDb): void {
            $mock->shouldReceive('listDatabases')->with(1)->once()->andReturn([$mockDb]);
        });

        $response = ForgeServer::tool(ListDatabasesTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('forge');
    });
});

describe('GetDatabaseTool', function (): void {
    it('requires server_id and database_id parameters', function (): void {
        $response = ForgeServer::tool(GetDatabaseTool::class, []);

        $response->assertHasErrors();
    });

    it('requires database_id when server_id provided', function (): void {
        $response = ForgeServer::tool(GetDatabaseTool::class, [
            'server_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects invalid parameter types', function (): void {
        $response = ForgeServer::tool(GetDatabaseTool::class, [
            'server_id' => 'invalid',
            'database_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects negative database_id', function (): void {
        $response = ForgeServer::tool(GetDatabaseTool::class, [
            'server_id' => 1,
            'database_id' => -1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects zero database_id', function (): void {
        $response = ForgeServer::tool(GetDatabaseTool::class, [
            'server_id' => 1,
            'database_id' => 0,
        ]);

        $response->assertHasErrors();
    });

    it('gets database details successfully', function (): void {
        $mockDb = new Database([
            'id' => 1,
            'name' => 'forge',
            'status' => 'installed',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockDb): void {
            $mock->shouldReceive('getDatabase')->with(1, 1)->once()->andReturn($mockDb);
        });

        $response = ForgeServer::tool(GetDatabaseTool::class, [
            'server_id' => 1,
            'database_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('forge')
            ->assertSee('installed');
    });

    it('handles database not found error', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('getDatabase')->with(1, 999)->once()->andThrow(new Exception('Database not found'));
        });

        $response = ForgeServer::tool(GetDatabaseTool::class, [
            'server_id' => 1,
            'database_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Database not found');
    });

    it('returns database with different status', function (): void {
        $mockDb = new Database([
            'id' => 2,
            'name' => 'production_db',
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockDb): void {
            $mock->shouldReceive('getDatabase')->with(1, 2)->once()->andReturn($mockDb);
        });

        $response = ForgeServer::tool(GetDatabaseTool::class, [
            'server_id' => 1,
            'database_id' => 2,
        ]);

        $response
            ->assertOk()
            ->assertSee('production_db')
            ->assertSee('"status": "creating"');
    });
});

describe('ListScheduledJobsTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListScheduledJobsTool::class, []);

        $response->assertHasErrors();
    });

    it('lists scheduled jobs successfully', function (): void {
        $mockJob = new Job([
            'id' => 1,
            'command' => 'php artisan schedule:run',
            'user' => 'forge',
            'frequency' => 'minutely',
            'cron' => '* * * * *',
            'status' => 'installed',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockJob): void {
            $mock->shouldReceive('listJobs')->with(1)->once()->andReturn([$mockJob]);
        });

        $response = ForgeServer::tool(ListScheduledJobsTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('schedule:run');
    });
});

describe('GetScheduledJobTool', function (): void {
    it('requires server_id and job_id parameters', function (): void {
        $response = ForgeServer::tool(GetScheduledJobTool::class, []);

        $response->assertHasErrors();
    });

    it('requires job_id when server_id provided', function (): void {
        $response = ForgeServer::tool(GetScheduledJobTool::class, [
            'server_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects invalid parameter types', function (): void {
        $response = ForgeServer::tool(GetScheduledJobTool::class, [
            'server_id' => 'invalid',
            'job_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects negative job_id', function (): void {
        $response = ForgeServer::tool(GetScheduledJobTool::class, [
            'server_id' => 1,
            'job_id' => -1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects zero job_id', function (): void {
        $response = ForgeServer::tool(GetScheduledJobTool::class, [
            'server_id' => 1,
            'job_id' => 0,
        ]);

        $response->assertHasErrors();
    });

    it('gets scheduled job details successfully', function (): void {
        $mockJob = new Job([
            'id' => 1,
            'command' => 'php artisan schedule:run',
            'user' => 'forge',
            'frequency' => 'minutely',
            'cron' => '* * * * *',
            'status' => 'installed',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockJob): void {
            $mock->shouldReceive('getJob')->with(1, 1)->once()->andReturn($mockJob);
        });

        $response = ForgeServer::tool(GetScheduledJobTool::class, [
            'server_id' => 1,
            'job_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('schedule:run')
            ->assertSee('minutely')
            ->assertSee('* * * * *');
    });

    it('handles job not found error', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('getJob')->with(1, 999)->once()->andThrow(new Exception('Job not found'));
        });

        $response = ForgeServer::tool(GetScheduledJobTool::class, [
            'server_id' => 1,
            'job_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Job not found');
    });

    it('returns job with custom frequency', function (): void {
        $mockJob = new Job([
            'id' => 2,
            'command' => 'php artisan backup:run',
            'user' => 'root',
            'frequency' => 'daily',
            'cron' => '0 0 * * *',
            'status' => 'installed',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockJob): void {
            $mock->shouldReceive('getJob')->with(1, 2)->once()->andReturn($mockJob);
        });

        $response = ForgeServer::tool(GetScheduledJobTool::class, [
            'server_id' => 1,
            'job_id' => 2,
        ]);

        $response
            ->assertOk()
            ->assertSee('backup:run')
            ->assertSee('"frequency": "daily"')
            ->assertSee('0 0 * * *');
    });
});

describe('ListDaemonsTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListDaemonsTool::class, []);

        $response->assertHasErrors();
    });

    it('lists daemons successfully', function (): void {
        $mockDaemon = new Daemon([
            'id' => 1,
            'command' => 'php artisan horizon',
            'user' => 'forge',
            'directory' => '/home/forge/example.com',
            'processes' => 1,
            'startsecs' => 1,
            'status' => 'installed',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockDaemon): void {
            $mock->shouldReceive('listDaemons')->with(1)->once()->andReturn([$mockDaemon]);
        });

        $response = ForgeServer::tool(ListDaemonsTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('horizon');
    });
});

describe('GetDaemonTool', function (): void {
    it('requires server_id and daemon_id parameters', function (): void {
        $response = ForgeServer::tool(GetDaemonTool::class, []);

        $response->assertHasErrors();
    });

    it('requires daemon_id when server_id provided', function (): void {
        $response = ForgeServer::tool(GetDaemonTool::class, [
            'server_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects invalid parameter types', function (): void {
        $response = ForgeServer::tool(GetDaemonTool::class, [
            'server_id' => 'invalid',
            'daemon_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects negative daemon_id', function (): void {
        $response = ForgeServer::tool(GetDaemonTool::class, [
            'server_id' => 1,
            'daemon_id' => -1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects zero daemon_id', function (): void {
        $response = ForgeServer::tool(GetDaemonTool::class, [
            'server_id' => 1,
            'daemon_id' => 0,
        ]);

        $response->assertHasErrors();
    });

    it('gets daemon details successfully', function (): void {
        $mockDaemon = new Daemon([
            'id' => 1,
            'command' => 'php artisan horizon',
            'user' => 'forge',
            'directory' => '/home/forge/example.com',
            'processes' => 1,
            'status' => 'installed',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockDaemon): void {
            $mock->shouldReceive('getDaemon')->with(1, 1)->once()->andReturn($mockDaemon);
        });

        $response = ForgeServer::tool(GetDaemonTool::class, [
            'server_id' => 1,
            'daemon_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('horizon')
            ->assertSee('example.com')
            ->assertSee('"processes": 1');
    });

    it('handles daemon not found error', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('getDaemon')->with(1, 999)->once()->andThrow(new Exception('Daemon not found'));
        });

        $response = ForgeServer::tool(GetDaemonTool::class, [
            'server_id' => 1,
            'daemon_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Daemon not found');
    });

    it('returns daemon with multiple processes', function (): void {
        $mockDaemon = new Daemon([
            'id' => 2,
            'command' => 'php artisan queue:work',
            'user' => 'root',
            'directory' => '/var/www/app',
            'processes' => 5,
            'status' => 'installed',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockDaemon): void {
            $mock->shouldReceive('getDaemon')->with(1, 2)->once()->andReturn($mockDaemon);
        });

        $response = ForgeServer::tool(GetDaemonTool::class, [
            'server_id' => 1,
            'daemon_id' => 2,
        ]);

        $response
            ->assertOk()
            ->assertSee('queue:work')
            ->assertSee('"processes": 5')
            ->assertSee('"user": "root"');
    });
});

describe('ListFirewallRulesTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListFirewallRulesTool::class, []);

        $response->assertHasErrors();
    });

    it('lists firewall rules successfully', function (): void {
        $mockRule = new FirewallRule([
            'id' => 1,
            'name' => 'SSH',
            'port' => '22',
            'type' => 'allow',
            'ipAddress' => null,
            'status' => 'installed',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockRule): void {
            $mock->shouldReceive('listFirewallRules')->with(1)->once()->andReturn([$mockRule]);
        });

        $response = ForgeServer::tool(ListFirewallRulesTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('SSH');
    });
});

describe('GetFirewallRuleTool', function (): void {
    it('requires server_id and rule_id parameters', function (): void {
        $response = ForgeServer::tool(GetFirewallRuleTool::class, []);

        $response->assertHasErrors();
    });

    it('requires rule_id when server_id provided', function (): void {
        $response = ForgeServer::tool(GetFirewallRuleTool::class, [
            'server_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects invalid parameter types', function (): void {
        $response = ForgeServer::tool(GetFirewallRuleTool::class, [
            'server_id' => 'invalid',
            'rule_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects negative rule_id', function (): void {
        $response = ForgeServer::tool(GetFirewallRuleTool::class, [
            'server_id' => 1,
            'rule_id' => -1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects zero rule_id', function (): void {
        $response = ForgeServer::tool(GetFirewallRuleTool::class, [
            'server_id' => 1,
            'rule_id' => 0,
        ]);

        $response->assertHasErrors();
    });

    it('gets firewall rule details successfully', function (): void {
        $mockRule = new FirewallRule([
            'id' => 1,
            'name' => 'SSH',
            'port' => '22',
            'type' => 'allow',
            'ipAddress' => null,
            'status' => 'installed',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockRule): void {
            $mock->shouldReceive('getFirewallRule')->with(1, 1)->once()->andReturn($mockRule);
        });

        $response = ForgeServer::tool(GetFirewallRuleTool::class, [
            'server_id' => 1,
            'rule_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('SSH')
            ->assertSee('"port": "22"')
            ->assertSee('"type": "allow"');
    });

    it('handles rule not found error', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('getFirewallRule')->with(1, 999)->once()->andThrow(new Exception('Firewall rule not found'));
        });

        $response = ForgeServer::tool(GetFirewallRuleTool::class, [
            'server_id' => 1,
            'rule_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Firewall rule not found');
    });

    it('returns rule with specific IP address', function (): void {
        $mockRule = new FirewallRule([
            'id' => 2,
            'name' => 'Custom MySQL',
            'port' => '3306',
            'type' => 'allow',
            'ipAddress' => '192.168.1.100',
            'status' => 'installed',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockRule): void {
            $mock->shouldReceive('getFirewallRule')->with(1, 2)->once()->andReturn($mockRule);
        });

        $response = ForgeServer::tool(GetFirewallRuleTool::class, [
            'server_id' => 1,
            'rule_id' => 2,
        ]);

        $response
            ->assertOk()
            ->assertSee('Custom MySQL')
            ->assertSee('"port": "3306"')
            ->assertSee('192.168.1.100');
    });

    it('returns deny rule', function (): void {
        $mockRule = new FirewallRule([
            'id' => 3,
            'name' => 'Block Port',
            'port' => '8080',
            'type' => 'deny',
            'ipAddress' => null,
            'status' => 'installed',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockRule): void {
            $mock->shouldReceive('getFirewallRule')->with(1, 3)->once()->andReturn($mockRule);
        });

        $response = ForgeServer::tool(GetFirewallRuleTool::class, [
            'server_id' => 1,
            'rule_id' => 3,
        ]);

        $response
            ->assertOk()
            ->assertSee('Block Port')
            ->assertSee('"type": "deny"')
            ->assertSee('8080');
    });
});

describe('RebootServerTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(RebootServerTool::class, []);

        $response->assertHasErrors();
    });

    it('rejects invalid server_id', function (): void {
        $response = ForgeServer::tool(RebootServerTool::class, [
            'server_id' => 'invalid',
        ]);

        $response->assertHasErrors();
    });

    it('reboots server successfully', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('rebootServer')->with(1)->once();
        });

        $response = ForgeServer::tool(RebootServerTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('reboot');
    });

    it('handles server not found error', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('rebootServer')->with(999)->once()->andThrow(new Exception('Server not found'));
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
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('rebootServer')->with(1)->once()->andThrow(new Exception('Permission denied'));
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
        $mockCert = new Certificate([
            'id' => 1,
            'domain' => 'example.com',
            'type' => 'letsencrypt',
            'status' => 'installing',
            'active' => false,
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockCert): void {
            $mock->shouldReceive('obtainLetsEncryptCertificate')
                ->with(1, 1, ['domains' => ['example.com']])
                ->once()
                ->andReturn($mockCert);
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
        $mockCert = new Certificate([
            'id' => 1,
            'domain' => 'example.com',
            'type' => 'letsencrypt',
            'status' => 'installing',
            'active' => false,
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockCert): void {
            $mock->shouldReceive('obtainLetsEncryptCertificate')
                ->with(1, 1, ['domains' => ['example.com', 'www.example.com']])
                ->once()
                ->andReturn($mockCert);
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
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('obtainLetsEncryptCertificate')
                ->with(1, 1, ['domains' => ['invalid.example.com']])
                ->once()
                ->andThrow(new Exception('DNS validation failed'));
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
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('obtainLetsEncryptCertificate')
                ->with(1, 1, ['domains' => ['example.com']])
                ->once()
                ->andThrow(new Exception('Rate limit exceeded'));
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
    it('handles missing migration parameter', function (): void {
        $response = ForgeServer::prompt(DeployApplicationPrompt::class, [
            'server_id' => '123',
            'site_id' => '456',
        ]);

        $response
            ->assertOk()
            ->assertSee('migrations will be executed');
    });

    it('handles run_migrations false', function (): void {
        $response = ForgeServer::prompt(DeployApplicationPrompt::class, [
            'server_id' => '123',
            'site_id' => '456',
            'run_migrations' => 'false',
        ]);

        $response
            ->assertOk()
            ->assertSee('migrations will NOT be run');
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
