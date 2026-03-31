<?php

declare(strict_types=1);

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Resources\SiteResource;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Sites\{ChangePhpVersionTool, CreateSiteTool, DeleteSiteTool, GetSiteTool, InstallPhpMyAdminTool, InstallWordPressTool, ListSitesTool, UninstallPhpMyAdminTool, UninstallWordPressTool, UpdateSiteTool};
use App\Integrations\Forge\Data\Sites\{CreateSiteData, SiteCollectionData, SiteData, UpdateSiteData};

beforeEach(function (): void {
    config([
        'services.forge.api_token' => 'test-token',
        'services.forge.organization' => 'test-org',
    ]);
});

function createMockSite(int $id = 1, int $serverId = 1, string $name = 'example.com'): SiteData
{
    return SiteData::from([
        'id' => $id,
        'server_id' => $serverId,
        'name' => $name,
        'status' => 'installed',
        'created_at' => '2024-01-01T00:00:00Z',
        'url' => 'http://' . $name,
        'user' => 'forge',
        'https' => false,
        'web_directory' => '/home/forge/' . $name . '/public',
        'root_directory' => '/home/forge/' . $name,
        'aliases' => [],
        'php_version' => 'php82',
        'quick_deploy' => false,
        'wildcards' => false,
        'repository' => ['provider' => 'github', 'url' => 'git@github.com:test/repo.git', 'branch' => 'main', 'status' => 'installed'],
        'app_type' => 'php',
        'tags' => [],
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
            $siteResource->shouldReceive('list')->with(Mockery::any(), null, 30)->once()->andReturn($collection);
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

describe('Site Tools Structure', function (): void {
    it('all site tools can be instantiated', function (): void {
        $tools = [
            ListSitesTool::class,
            GetSiteTool::class,
            CreateSiteTool::class,
            UpdateSiteTool::class,
            DeleteSiteTool::class,
            ChangePhpVersionTool::class,
            InstallWordPressTool::class,
            UninstallWordPressTool::class,
            InstallPhpMyAdminTool::class,
            UninstallPhpMyAdminTool::class,
        ];

        foreach ($tools as $toolClass) {
            $tool = app($toolClass);
            expect($tool->name())->toBeString()->not->toBeEmpty();
            expect($tool->description())->toBeString()->not->toBeEmpty();
        }
    });
});
