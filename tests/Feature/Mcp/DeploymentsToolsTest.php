<?php

declare(strict_types=1);

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Resources\SiteResource;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Deployments\{DeploySiteTool, GetDeploymentHistoryDeploymentTool, GetDeploymentScriptTool, ListDeploymentHistoryTool, UpdateDeploymentScriptTool};

beforeEach(function (): void {
    config([
        'services.forge.api_token' => 'test-token',
        'services.forge.organization' => 'test-org',
    ]);
});

describe('DeploySiteTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(DeploySiteTool::class, []);

        $response->assertHasErrors();
    });

    it('deploys site successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('deploy')->with(1, 1)->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(DeploySiteTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles deployment failure', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('deploy')->andThrow(new Exception('Deployment failed'));
            $mock->shouldReceive('sites')->andReturn($siteResource);
        });

        $response = ForgeServer::tool(DeploySiteTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('GetDeploymentScriptTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetDeploymentScriptTool::class, []);

        $response->assertHasErrors();
    });

    it('gets deployment script successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('deploymentScript')->with(1, 1)->once()->andReturn('cd /home/forge/site && git pull');
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetDeploymentScriptTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('git pull');
    });
});

describe('UpdateDeploymentScriptTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(UpdateDeploymentScriptTool::class, []);

        $response->assertHasErrors();
    });

    it('requires content parameter', function (): void {
        $response = ForgeServer::tool(UpdateDeploymentScriptTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('updates deployment script successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('updateDeploymentScript')->with(1, 1, Mockery::any())->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UpdateDeploymentScriptTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'content' => 'cd /home/forge/site && git pull && composer install',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('ListDeploymentHistoryTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(ListDeploymentHistoryTool::class, []);

        $response->assertHasErrors();
    });

    it('lists deployment history successfully', function (): void {
        $mockHistory = [
            [
                'id' => 1,
                'server_id' => 1,
                'site_id' => 1,
                'commit_hash' => 'abc123',
                'commit_author' => 'John Doe',
                'commit_message' => 'Update feature',
                'status' => 'finished',
                'started_at' => '2024-01-01T00:00:00Z',
                'ended_at' => '2024-01-01T00:01:00Z',
            ],
        ];

        $this->mock(ForgeClient::class, function ($mock) use ($mockHistory): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('deploymentHistory')->with(1, 1)->once()->andReturn($mockHistory);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ListDeploymentHistoryTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('abc123');
    });
});

describe('GetDeploymentHistoryDeploymentTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(GetDeploymentHistoryDeploymentTool::class, []);

        $response->assertHasErrors();
    });

    it('gets specific deployment successfully', function (): void {
        $mockDeployment = [
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'commit_hash' => 'abc123',
            'commit_author' => 'John Doe',
            'commit_message' => 'Update feature',
            'status' => 'finished',
        ];

        $this->mock(ForgeClient::class, function ($mock) use ($mockDeployment): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('deploymentHistoryDeployment')->with(1, 1, 1)->once()->andReturn($mockDeployment);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetDeploymentHistoryDeploymentTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'deployment_id' => 1,
        ]);

        $response->assertOk()->assertSee('abc123');
    });
});

describe('Deployment Tools Structure', function (): void {
    it('all deployment tools can be instantiated', function (): void {
        $tools = [
            DeploySiteTool::class,
            GetDeploymentScriptTool::class,
            UpdateDeploymentScriptTool::class,
            ListDeploymentHistoryTool::class,
            GetDeploymentHistoryDeploymentTool::class,
        ];

        foreach ($tools as $toolClass) {
            $tool = app($toolClass);
            expect($tool->name())->toBeString()->not->toBeEmpty();
            expect($tool->description())->toBeString()->not->toBeEmpty();
        }
    });
});
