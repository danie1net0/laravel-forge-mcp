<?php

declare(strict_types=1);

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Resources\SiteResource;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Sites\{ChangePhpVersionTool, ClearSiteLogTool, CreateSiteTool, DeleteSiteTool, GetLoadBalancingTool, GetPackagesAuthTool, GetSiteLogTool, GetSiteTool, InstallPhpMyAdminTool, InstallWordPressTool, ListAliasesTool, ListSitesTool, UninstallPhpMyAdminTool, UninstallWordPressTool, UpdateAliasesTool, UpdateLoadBalancingTool, UpdatePackagesAuthTool, UpdateSiteTool};
use App\Integrations\Forge\Data\Sites\{CreateSiteData, SiteCollectionData, SiteData, UpdateSiteData};

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

function createMockSite(int $id = 1, int $serverId = 1, string $name = 'example.com'): SiteData
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

describe('ListSitesTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListSitesTool::class, []);

        $response->assertHasErrors();
    });

    it('lists sites successfully', function (): void {
        $mockSite = createMockSite();

        $this->mock(ForgeClient::class, function ($mock) use ($mockSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $collection = new SiteCollectionData(sites: [$mockSite]);
            $siteResource->shouldReceive('list')->with(Mockery::any())->once()->andReturn($collection);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ListSitesTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('example.com');
    });

    it('returns empty list when no sites exist', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $collection = new SiteCollectionData(sites: []);
            $siteResource->shouldReceive('list')->once()->andReturn($collection);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ListSitesTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"count": 0');
    });
});

describe('GetSiteTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetSiteTool::class, []);

        $response->assertHasErrors();
    });

    it('requires site_id when server_id provided', function (): void {
        $response = ForgeServer::tool(GetSiteTool::class, ['server_id' => 1]);

        $response->assertHasErrors();
    });

    it('gets site details successfully', function (): void {
        $mockSite = createMockSite();

        $this->mock(ForgeClient::class, function ($mock) use ($mockSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->with(1, 1)->once()->andReturn($mockSite);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetSiteTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('example.com');
    });

    it('handles site not found', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('get')->andThrow(new Exception('Site not found'));
            $mock->shouldReceive('sites')->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetSiteTool::class, [
            'server_id' => 1,
            'site_id' => 999,
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('CreateSiteTool', function (): void {
    it('requires mandatory parameters', function (): void {
        $response = ForgeServer::tool(CreateSiteTool::class, []);

        $response->assertHasErrors();
    });

    it('requires domain parameter', function (): void {
        $response = ForgeServer::tool(CreateSiteTool::class, [
            'server_id' => 1,
            'project_type' => 'php',
        ]);

        $response->assertHasErrors();
    });

    it('creates site successfully', function (): void {
        $mockSite = createMockSite();

        $this->mock(ForgeClient::class, function ($mock) use ($mockSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('create')
                ->with(1, Mockery::type(CreateSiteData::class))
                ->once()
                ->andReturn($mockSite);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(CreateSiteTool::class, [
            'server_id' => 1,
            'domain' => 'example.com',
            'project_type' => 'php',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('creates site with directory option', function (): void {
        $mockSite = createMockSite();

        $this->mock(ForgeClient::class, function ($mock) use ($mockSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('create')->once()->andReturn($mockSite);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(CreateSiteTool::class, [
            'server_id' => 1,
            'domain' => 'example.com',
            'project_type' => 'php',
            'directory' => '/public',
        ]);

        $response->assertOk();
    });
});

describe('UpdateSiteTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(UpdateSiteTool::class, []);

        $response->assertHasErrors();
    });

    it('updates site successfully', function (): void {
        $mockSite = createMockSite();

        $this->mock(ForgeClient::class, function ($mock) use ($mockSite): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('update')
                ->with(1, 1, Mockery::type(UpdateSiteData::class))
                ->once()
                ->andReturn($mockSite);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UpdateSiteTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'directory' => '/public_html',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('DeleteSiteTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(DeleteSiteTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes site successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('delete')->with(1, 1)->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(DeleteSiteTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('GetSiteLogTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetSiteLogTool::class, []);

        $response->assertHasErrors();
    });

    it('gets site log successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('log')->with(1, 1)->once()->andReturn(['content' => 'Log content']);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetSiteLogTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('Log content');
    });
});

describe('ClearSiteLogTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(ClearSiteLogTool::class, []);

        $response->assertHasErrors();
    });

    it('clears site log successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('clearLog')->with(1, 1)->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ClearSiteLogTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('ChangePhpVersionTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(ChangePhpVersionTool::class, []);

        $response->assertHasErrors();
    });

    it('requires version parameter', function (): void {
        $response = ForgeServer::tool(ChangePhpVersionTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('changes PHP version successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('changePhpVersion')->with(1, 1, 'php84')->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ChangePhpVersionTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'version' => 'php84',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('ListAliasesTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(ListAliasesTool::class, []);

        $response->assertHasErrors();
    });

    it('lists aliases successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('listAliases')->with(1, 1)->once()->andReturn(['www.example.com', 'api.example.com']);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ListAliasesTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('www.example.com');
    });
});

describe('UpdateAliasesTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(UpdateAliasesTool::class, []);

        $response->assertHasErrors();
    });

    it('updates aliases successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('updateAliases')->with(1, 1, ['www.example.com'])->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UpdateAliasesTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'aliases' => ['www.example.com'],
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('GetLoadBalancingTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetLoadBalancingTool::class, []);

        $response->assertHasErrors();
    });

    it('gets load balancing config successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('getLoadBalancing')->with(1, 1)->once()->andReturn([
                'servers' => [['id' => 2, 'weight' => 5]],
            ]);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetLoadBalancingTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk();
    });
});

describe('UpdateLoadBalancingTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(UpdateLoadBalancingTool::class, []);

        $response->assertHasErrors();
    });

    it('updates load balancing successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('updateLoadBalancing')->with(1, 1, Mockery::any(), Mockery::any())->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UpdateLoadBalancingTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'servers' => [['id' => 2, 'weight' => 5]],
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('InstallWordPressTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(InstallWordPressTool::class, []);

        $response->assertHasErrors();
    });

    it('installs WordPress successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('installWordPress')->with(1, 1, 'wordpress', 'admin', null)->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(InstallWordPressTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'database' => 'wordpress',
            'user' => 'admin',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('UninstallWordPressTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(UninstallWordPressTool::class, []);

        $response->assertHasErrors();
    });

    it('uninstalls WordPress successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('uninstallWordPress')->with(1, 1)->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UninstallWordPressTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('InstallPhpMyAdminTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(InstallPhpMyAdminTool::class, []);

        $response->assertHasErrors();
    });

    it('installs phpMyAdmin successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('installPhpMyAdmin')->with(1, 1)->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(InstallPhpMyAdminTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('UninstallPhpMyAdminTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(UninstallPhpMyAdminTool::class, []);

        $response->assertHasErrors();
    });

    it('uninstalls phpMyAdmin successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('uninstallPhpMyAdmin')->with(1, 1)->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UninstallPhpMyAdminTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('GetPackagesAuthTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetPackagesAuthTool::class, []);

        $response->assertHasErrors();
    });

    it('gets packages auth successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('getPackagesAuth')->with(1, 1)->once()->andReturn(['auth' => []]);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetPackagesAuthTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk();
    });
});

describe('UpdatePackagesAuthTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(UpdatePackagesAuthTool::class, []);

        $response->assertHasErrors();
    });

    it('updates packages auth successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('updatePackagesAuth')->with(1, 1, Mockery::any())->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UpdatePackagesAuthTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'packages' => ['github-oauth' => ['github.com' => 'token']],
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('Site Tools Structure', function (): void {
    it('all site tools can be instantiated', function (): void {
        $tools = [
            ListSitesTool::class,
            GetSiteTool::class,
            CreateSiteTool::class,
            UpdateSiteTool::class,
            DeleteSiteTool::class,
            GetSiteLogTool::class,
            ClearSiteLogTool::class,
            ChangePhpVersionTool::class,
            ListAliasesTool::class,
            UpdateAliasesTool::class,
            GetLoadBalancingTool::class,
            UpdateLoadBalancingTool::class,
            InstallWordPressTool::class,
            UninstallWordPressTool::class,
            InstallPhpMyAdminTool::class,
            UninstallPhpMyAdminTool::class,
            GetPackagesAuthTool::class,
            UpdatePackagesAuthTool::class,
        ];

        foreach ($tools as $toolClass) {
            $tool = app($toolClass);
            expect($tool->name())->toBeString()->not->toBeEmpty();
            expect($tool->description())->toBeString()->not->toBeEmpty();
        }
    });
});
