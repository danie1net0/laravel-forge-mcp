<?php

declare(strict_types=1);

use App\Mcp\Prompts\DeployApplicationPrompt;
use App\Mcp\Resources\{DeploymentGuidelinesResource, ForgeApiDocsResource};
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Certificates\{GetCertificateTool, ListCertificatesTool, ObtainLetsEncryptCertificateTool};
use App\Mcp\Tools\Daemons\{CreateDaemonTool, GetDaemonTool, ListDaemonsTool};
use App\Mcp\Tools\Databases\{CreateDatabaseTool, CreateDatabaseUserTool, GetDatabaseTool, ListDatabasesTool};
use App\Mcp\Tools\Deployments\{DeploySiteTool, GetDeploymentLogTool, GetDeploymentScriptTool};
use App\Mcp\Tools\Firewall\{CreateFirewallRuleTool, GetFirewallRuleTool, ListFirewallRulesTool};
use App\Mcp\Tools\Jobs\{CreateScheduledJobTool, GetScheduledJobTool, ListScheduledJobsTool};
use App\Mcp\Tools\Servers\{GetServerTool, ListServersTool, RebootServerTool};
use App\Mcp\Tools\Sites\{GetSiteLogTool, GetSiteTool, ListSitesTool};
use App\Services\ForgeService;
use Laravel\Forge\Resources\{Certificate, Daemon, Database, DatabaseUser, FirewallRule, Job, Server, Site};

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

describe('GetSiteLogTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetSiteLogTool::class, []);

        $response->assertHasErrors();
    });

    it('requires site_id when server_id provided', function (): void {
        $response = ForgeServer::tool(GetSiteLogTool::class, [
            'server_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects invalid parameter types', function (): void {
        $response = ForgeServer::tool(GetSiteLogTool::class, [
            'server_id' => 'invalid',
            'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('gets site log successfully', function (): void {
        $mockLog = ['content' => "Access log content here\nError log content here"];

        $this->mock(ForgeService::class, function ($mock) use ($mockLog): void {
            $mock->shouldReceive('siteLog')->with(1, 1)->once()->andReturn($mockLog);
        });

        $response = ForgeServer::tool(GetSiteLogTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Access log');
    });

    it('handles empty site log', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('siteLog')->with(1, 1)->once()->andReturn(['content' => '']);
        });

        $response = ForgeServer::tool(GetSiteLogTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true');
    });

    it('handles site log with errors', function (): void {
        $mockLog = ['content' => "[error] 2024/01/01 12:00:00 [error] 123#456: *1 FastCGI sent in stderr: \"PHP message: PHP Fatal error: Uncaught Exception\""];

        $this->mock(ForgeService::class, function ($mock) use ($mockLog): void {
            $mock->shouldReceive('siteLog')->with(1, 1)->once()->andReturn($mockLog);
        });

        $response = ForgeServer::tool(GetSiteLogTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('[error]')
            ->assertSee('Fatal error');
    });

    it('handles API errors', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('siteLog')->with(1, 999)->once()->andThrow(new Exception('Site not found'));
        });

        $response = ForgeServer::tool(GetSiteLogTool::class, [
            'server_id' => 1,
            'site_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Site not found');
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

describe('CreateDatabaseTool', function (): void {
    it('requires server_id and name parameters', function (): void {
        $response = ForgeServer::tool(CreateDatabaseTool::class, []);

        $response->assertHasErrors();
    });

    it('requires name when server_id provided', function (): void {
        $response = ForgeServer::tool(CreateDatabaseTool::class, [
            'server_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('rejects invalid parameter types', function (): void {
        $response = ForgeServer::tool(CreateDatabaseTool::class, [
            'server_id' => 'invalid',
            'name' => 'test_db',
        ]);

        $response->assertHasErrors();
    });

    it('creates database successfully', function (): void {
        $mockDb = new Database([
            'id' => 1,
            'name' => 'test_db',
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockDb): void {
            $mock->shouldReceive('createDatabase')
                ->with(1, ['name' => 'test_db'])
                ->once()
                ->andReturn($mockDb);
        });

        $response = ForgeServer::tool(CreateDatabaseTool::class, [
            'server_id' => 1,
            'name' => 'test_db',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('test_db')
            ->assertSee('creating');
    });

    it('creates database with custom user and password', function (): void {
        $mockDb = new Database([
            'id' => 2,
            'name' => 'production_db',
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockDb): void {
            $mock->shouldReceive('createDatabase')
                ->with(1, [
                    'name' => 'production_db',
                    'user' => 'dbuser',
                    'password' => 'securepassword123',
                ])
                ->once()
                ->andReturn($mockDb);
        });

        $response = ForgeServer::tool(CreateDatabaseTool::class, [
            'server_id' => 1,
            'name' => 'production_db',
            'user' => 'dbuser',
            'password' => 'securepassword123',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('production_db');
    });

    it('handles database name conflict', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('createDatabase')
                ->with(1, ['name' => 'existing_db'])
                ->once()
                ->andThrow(new Exception('Database already exists'));
        });

        $response = ForgeServer::tool(CreateDatabaseTool::class, [
            'server_id' => 1,
            'name' => 'existing_db',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Database already exists');
    });
});

describe('CreateDatabaseUserTool', function (): void {
    it('requires server_id, name and password parameters', function (): void {
        $response = ForgeServer::tool(CreateDatabaseUserTool::class, []);

        $response->assertHasErrors();
    });

    it('requires password when server_id and name provided', function (): void {
        $response = ForgeServer::tool(CreateDatabaseUserTool::class, [
            'server_id' => 1,
            'name' => 'dbuser',
        ]);

        $response->assertHasErrors();
    });

    it('rejects weak password', function (): void {
        $response = ForgeServer::tool(CreateDatabaseUserTool::class, [
            'server_id' => 1,
            'name' => 'dbuser',
            'password' => 'weak',
        ]);

        $response->assertHasErrors();
    });

    it('creates database user successfully', function (): void {
        $mockUser = new DatabaseUser([
            'id' => 1,
            'name' => 'dbuser',
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockUser): void {
            $mock->shouldReceive('createDatabaseUser')
                ->with(1, [
                    'name' => 'dbuser',
                    'password' => 'securepass123',
                ])
                ->once()
                ->andReturn($mockUser);
        });

        $response = ForgeServer::tool(CreateDatabaseUserTool::class, [
            'server_id' => 1,
            'name' => 'dbuser',
            'password' => 'securepass123',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('dbuser')
            ->assertSee('creating');
    });

    it('creates user with database access', function (): void {
        $mockUser = new DatabaseUser([
            'id' => 2,
            'name' => 'appuser',
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockUser): void {
            $mock->shouldReceive('createDatabaseUser')
                ->with(1, [
                    'name' => 'appuser',
                    'password' => 'password123456',
                    'databases' => [1, 2],
                ])
                ->once()
                ->andReturn($mockUser);
        });

        $response = ForgeServer::tool(CreateDatabaseUserTool::class, [
            'server_id' => 1,
            'name' => 'appuser',
            'password' => 'password123456',
            'databases' => [1, 2],
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('appuser');
    });

    it('handles user creation error', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('createDatabaseUser')
                ->with(1, [
                    'name' => 'existing_user',
                    'password' => 'password123',
                ])
                ->once()
                ->andThrow(new Exception('User already exists'));
        });

        $response = ForgeServer::tool(CreateDatabaseUserTool::class, [
            'server_id' => 1,
            'name' => 'existing_user',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('User already exists');
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

describe('CreateScheduledJobTool', function (): void {
    it('requires server_id, command and frequency parameters', function (): void {
        $response = ForgeServer::tool(CreateScheduledJobTool::class, []);

        $response->assertHasErrors();
    });

    it('requires frequency when server_id and command provided', function (): void {
        $response = ForgeServer::tool(CreateScheduledJobTool::class, [
            'server_id' => 1,
            'command' => 'php artisan schedule:run',
        ]);

        $response->assertHasErrors();
    });

    it('rejects invalid frequency', function (): void {
        $response = ForgeServer::tool(CreateScheduledJobTool::class, [
            'server_id' => 1,
            'command' => 'php artisan schedule:run',
            'frequency' => 'invalid',
        ]);

        $response->assertHasErrors();
    });

    it('creates job with predefined frequency successfully', function (): void {
        $mockJob = new Job([
            'id' => 1,
            'command' => 'php artisan schedule:run',
            'user' => 'forge',
            'frequency' => 'minutely',
            'cron' => '* * * * *',
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockJob): void {
            $mock->shouldReceive('createJob')
                ->with(1, [
                    'command' => 'php artisan schedule:run',
                    'frequency' => 'minutely',
                ])
                ->once()
                ->andReturn($mockJob);
        });

        $response = ForgeServer::tool(CreateScheduledJobTool::class, [
            'server_id' => 1,
            'command' => 'php artisan schedule:run',
            'frequency' => 'minutely',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('schedule:run')
            ->assertSee('minutely');
    });

    it('creates job with custom cron expression', function (): void {
        $mockJob = new Job([
            'id' => 2,
            'command' => 'php artisan backup:run',
            'user' => 'forge',
            'frequency' => 'custom',
            'cron' => '0 2 * * 0',
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockJob): void {
            $mock->shouldReceive('createJob')
                ->with(1, [
                    'command' => 'php artisan backup:run',
                    'frequency' => 'custom',
                    'minute' => '0',
                    'hour' => '2',
                    'day' => '*',
                    'month' => '*',
                    'weekday' => '0',
                ])
                ->once()
                ->andReturn($mockJob);
        });

        $response = ForgeServer::tool(CreateScheduledJobTool::class, [
            'server_id' => 1,
            'command' => 'php artisan backup:run',
            'frequency' => 'custom',
            'minute' => '0',
            'hour' => '2',
            'day' => '*',
            'month' => '*',
            'weekday' => '0',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('backup:run');
    });

    it('creates job with custom user', function (): void {
        $mockJob = new Job([
            'id' => 3,
            'command' => 'php artisan queue:work',
            'user' => 'root',
            'frequency' => 'hourly',
            'cron' => '0 * * * *',
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockJob): void {
            $mock->shouldReceive('createJob')
                ->with(1, [
                    'command' => 'php artisan queue:work',
                    'frequency' => 'hourly',
                    'user' => 'root',
                ])
                ->once()
                ->andReturn($mockJob);
        });

        $response = ForgeServer::tool(CreateScheduledJobTool::class, [
            'server_id' => 1,
            'command' => 'php artisan queue:work',
            'frequency' => 'hourly',
            'user' => 'root',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('queue:work');
    });

    it('handles job creation error', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('createJob')
                ->with(1, [
                    'command' => 'invalid command',
                    'frequency' => 'minutely',
                ])
                ->once()
                ->andThrow(new Exception('Invalid command format'));
        });

        $response = ForgeServer::tool(CreateScheduledJobTool::class, [
            'server_id' => 1,
            'command' => 'invalid command',
            'frequency' => 'minutely',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Invalid command format');
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
            ->assertSee('example.com');
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

    it('returns daemon with different user', function (): void {
        $mockDaemon = new Daemon([
            'id' => 2,
            'command' => 'php artisan queue:work',
            'user' => 'root',
            'directory' => '/var/www/app',
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
            ->assertSee('"user": "root"');
    });
});

describe('CreateDaemonTool', function (): void {
    it('requires server_id, command and directory parameters', function (): void {
        $response = ForgeServer::tool(CreateDaemonTool::class, []);

        $response->assertHasErrors();
    });

    it('requires directory when server_id and command provided', function (): void {
        $response = ForgeServer::tool(CreateDaemonTool::class, [
            'server_id' => 1,
            'command' => 'php artisan horizon',
        ]);

        $response->assertHasErrors();
    });

    it('rejects invalid parameter types', function (): void {
        $response = ForgeServer::tool(CreateDaemonTool::class, [
            'server_id' => 'invalid',
            'command' => 'php artisan horizon',
            'directory' => '/home/forge/app',
        ]);

        $response->assertHasErrors();
    });

    it('creates daemon successfully', function (): void {
        $mockDaemon = new Daemon([
            'id' => 1,
            'command' => 'php artisan horizon',
            'user' => 'forge',
            'directory' => '/home/forge/example.com',
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockDaemon): void {
            $mock->shouldReceive('createDaemon')
                ->with(1, [
                    'command' => 'php artisan horizon',
                    'directory' => '/home/forge/example.com',
                ])
                ->once()
                ->andReturn($mockDaemon);
        });

        $response = ForgeServer::tool(CreateDaemonTool::class, [
            'server_id' => 1,
            'command' => 'php artisan horizon',
            'directory' => '/home/forge/example.com',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('horizon')
            ->assertSee('example.com');
    });

    it('creates daemon with optional parameters', function (): void {
        $mockDaemon = new Daemon([
            'id' => 2,
            'command' => 'php artisan queue:work',
            'user' => 'root',
            'directory' => '/var/www/app',
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockDaemon): void {
            $mock->shouldReceive('createDaemon')
                ->with(1, [
                    'command' => 'php artisan queue:work',
                    'directory' => '/var/www/app',
                    'user' => 'root',
                    'processes' => 3,
                    'startsecs' => 5,
                ])
                ->once()
                ->andReturn($mockDaemon);
        });

        $response = ForgeServer::tool(CreateDaemonTool::class, [
            'server_id' => 1,
            'command' => 'php artisan queue:work',
            'directory' => '/var/www/app',
            'user' => 'root',
            'processes' => 3,
            'startsecs' => 5,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('queue:work');
    });

    it('creates reverb daemon', function (): void {
        $mockDaemon = new Daemon([
            'id' => 3,
            'command' => 'php artisan reverb:start',
            'user' => 'forge',
            'directory' => '/home/forge/app.com',
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockDaemon): void {
            $mock->shouldReceive('createDaemon')
                ->with(1, [
                    'command' => 'php artisan reverb:start',
                    'directory' => '/home/forge/app.com',
                ])
                ->once()
                ->andReturn($mockDaemon);
        });

        $response = ForgeServer::tool(CreateDaemonTool::class, [
            'server_id' => 1,
            'command' => 'php artisan reverb:start',
            'directory' => '/home/forge/app.com',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('reverb:start');
    });

    it('handles daemon creation error', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('createDaemon')
                ->with(1, [
                    'command' => 'invalid command',
                    'directory' => '/invalid/path',
                ])
                ->once()
                ->andThrow(new Exception('Invalid directory path'));
        });

        $response = ForgeServer::tool(CreateDaemonTool::class, [
            'server_id' => 1,
            'command' => 'invalid command',
            'directory' => '/invalid/path',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Invalid directory path');
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
            ->assertSee('"port": "22"');
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

    it('returns rule for different port', function (): void {
        $mockRule = new FirewallRule([
            'id' => 3,
            'name' => 'Block Port',
            'port' => '8080',
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
            ->assertSee('8080');
    });
});

describe('CreateFirewallRuleTool', function (): void {
    it('requires server_id, name and port parameters', function (): void {
        $response = ForgeServer::tool(CreateFirewallRuleTool::class, []);

        $response->assertHasErrors();
    });

    it('requires port when server_id and name provided', function (): void {
        $response = ForgeServer::tool(CreateFirewallRuleTool::class, [
            'server_id' => 1,
            'name' => 'HTTPS',
        ]);

        $response->assertHasErrors();
    });

    it('rejects invalid parameter types', function (): void {
        $response = ForgeServer::tool(CreateFirewallRuleTool::class, [
            'server_id' => 'invalid',
            'name' => 'HTTPS',
            'port' => '443',
        ]);

        $response->assertHasErrors();
    });

    it('creates firewall rule successfully', function (): void {
        $mockRule = new FirewallRule([
            'id' => 1,
            'name' => 'HTTPS',
            'port' => '443',
            'ipAddress' => null,
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockRule): void {
            $mock->shouldReceive('createFirewallRule')
                ->with(1, [
                    'name' => 'HTTPS',
                    'port' => '443',
                ])
                ->once()
                ->andReturn($mockRule);
        });

        $response = ForgeServer::tool(CreateFirewallRuleTool::class, [
            'server_id' => 1,
            'name' => 'HTTPS',
            'port' => '443',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('HTTPS')
            ->assertSee('"port": "443"');
    });

    it('creates rule with IP restriction', function (): void {
        $mockRule = new FirewallRule([
            'id' => 2,
            'name' => 'MySQL Access',
            'port' => '3306',
            'ipAddress' => '192.168.1.100',
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockRule): void {
            $mock->shouldReceive('createFirewallRule')
                ->with(1, [
                    'name' => 'MySQL Access',
                    'port' => '3306',
                    'ip_address' => '192.168.1.100',
                ])
                ->once()
                ->andReturn($mockRule);
        });

        $response = ForgeServer::tool(CreateFirewallRuleTool::class, [
            'server_id' => 1,
            'name' => 'MySQL Access',
            'port' => '3306',
            'ip_address' => '192.168.1.100',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('MySQL Access')
            ->assertSee('192.168.1.100');
    });

    it('creates rule with port range', function (): void {
        $mockRule = new FirewallRule([
            'id' => 3,
            'name' => 'App Ports',
            'port' => '8000-9000',
            'ipAddress' => null,
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockRule): void {
            $mock->shouldReceive('createFirewallRule')
                ->with(1, [
                    'name' => 'App Ports',
                    'port' => '8000-9000',
                ])
                ->once()
                ->andReturn($mockRule);
        });

        $response = ForgeServer::tool(CreateFirewallRuleTool::class, [
            'server_id' => 1,
            'name' => 'App Ports',
            'port' => '8000-9000',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('App Ports')
            ->assertSee('8000-9000');
    });

    it('creates rule with CIDR notation', function (): void {
        $mockRule = new FirewallRule([
            'id' => 4,
            'name' => 'Office Network',
            'port' => '22',
            'ipAddress' => '192.168.1.0/24',
            'status' => 'creating',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockRule): void {
            $mock->shouldReceive('createFirewallRule')
                ->with(1, [
                    'name' => 'Office Network',
                    'port' => '22',
                    'ip_address' => '192.168.1.0/24',
                ])
                ->once()
                ->andReturn($mockRule);
        });

        $response = ForgeServer::tool(CreateFirewallRuleTool::class, [
            'server_id' => 1,
            'name' => 'Office Network',
            'port' => '22',
            'ip_address' => '192.168.1.0/24',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Office Network')
            ->assertSee('192.168.1.0');
    });

    it('handles rule creation error', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('createFirewallRule')
                ->with(1, [
                    'name' => 'Invalid Rule',
                    'port' => 'invalid',
                ])
                ->once()
                ->andThrow(new Exception('Invalid port format'));
        });

        $response = ForgeServer::tool(CreateFirewallRuleTool::class, [
            'server_id' => 1,
            'name' => 'Invalid Rule',
            'port' => 'invalid',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Invalid port format');
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
