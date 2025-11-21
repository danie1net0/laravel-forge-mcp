<?php

declare(strict_types=1);

use App\Mcp\Prompts\DeployApplicationPrompt;
use App\Mcp\Resources\{DeploymentGuidelinesResource, ForgeApiDocsResource};
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Certificates\{ListCertificatesTool, ObtainLetsEncryptCertificateTool};
use App\Mcp\Tools\Daemons\ListDaemonsTool;
use App\Mcp\Tools\Databases\ListDatabasesTool;
use App\Mcp\Tools\Deployments\{DeploySiteTool, GetDeploymentLogTool, GetDeploymentScriptTool};
use App\Mcp\Tools\Firewall\ListFirewallRulesTool;
use App\Mcp\Tools\Jobs\ListScheduledJobsTool;
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
            'ipAddress' => '192.168.1.1',
            'provider' => 'digitalocean',
            'region' => 'nyc1',
            'size' => '1gb',
            'phpVersion' => '8.2',
            'databaseType' => 'mysql',
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
            ->assertSee('test-server');
    });

    it('handles errors gracefully', function (): void {
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('listServers')->once()->andThrow(new Exception('API Error'));
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response
            ->assertOk()
            ->assertSee('API Error');
    });
});

describe('GetServerTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(GetServerTool::class, []);

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
            ->assertSee('test-server')
            ->assertSee('192.168.1.1');
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
            ->assertSee('example.com');
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
});

describe('GetDeploymentLogTool', function (): void {
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

describe('RebootServerTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(RebootServerTool::class, []);

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
            ->assertSee('reboot');
    });
});

describe('ObtainLetsEncryptCertificateTool', function (): void {
    it('requires server_id, site_id and domains parameters', function (): void {
        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, []);

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
            ->assertSee('initiated');
    });
});
