<?php

declare(strict_types=1);

use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Certificates\GetCertificateSigningRequestTool;
use App\Mcp\Tools\Commands\{ExecuteSiteCommandTool, GetSiteCommandTool, ListCommandHistoryTool};
use App\Mcp\Tools\Databases\{GetDatabaseUserTool, ListDatabaseUsersTool};
use App\Mcp\Tools\Deployments\{GetDeploymentHistoryDeploymentTool, GetDeploymentHistoryOutputTool, ListDeploymentHistoryTool};
use App\Integrations\Forge\Data\Databases\DatabaseUserData;
use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Resources\{CertificateResource, DatabaseUserResource, SiteResource};

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

describe('GetCertificateSigningRequestTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(GetCertificateSigningRequestTool::class, []);

        $response->assertHasErrors();
    });

    it('gets CSR successfully', function (): void {
        $mockCsr = "-----BEGIN CERTIFICATE REQUEST-----\nMIICvDCCAaQCAQAwdzELMAkGA...";

        $this->mock(ForgeClient::class, function ($mock) use ($mockCsr): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('signingRequest')
                ->with(1, 1, 1)
                ->once()
                ->andReturn($mockCsr);
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(GetCertificateSigningRequestTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'certificate_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('BEGIN CERTIFICATE REQUEST');
    });

    it('handles errors', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('signingRequest')
                ->with(1, 1, 999)
                ->once()
                ->andThrow(new Exception('Certificate not found'));
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(GetCertificateSigningRequestTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'certificate_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Certificate not found');
    });
});

describe('ListCommandHistoryTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(ListCommandHistoryTool::class, []);

        $response->assertHasErrors();
    });

    it('lists command history successfully', function (): void {
        $mockCommands = [[
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'command' => 'php artisan cache:clear',
            'status' => 'finished',
            'created_at' => '2024-01-01T00:00:00Z',
        ]];

        $this->mock(ForgeClient::class, function ($mock) use ($mockCommands): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('commandHistory')
                ->with(1, 1)
                ->once()
                ->andReturn($mockCommands);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ListCommandHistoryTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('cache:clear');
    });

    it('handles errors', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('commandHistory')
                ->with(1, 999)
                ->once()
                ->andThrow(new Exception('Site not found'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ListCommandHistoryTool::class, [
            'server_id' => 1,
            'site_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('GetSiteCommandTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(GetSiteCommandTool::class, []);

        $response->assertHasErrors();
    });

    it('gets command details successfully', function (): void {
        $mockCommandArray = [
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'command' => 'php artisan migrate',
            'status' => 'finished',
            'output' => 'Migration successful',
            'created_at' => '2024-01-01T00:00:00Z',
        ];

        $this->mock(ForgeClient::class, function ($mock) use ($mockCommandArray): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('getCommand')
                ->with(1, 1, 1)
                ->once()
                ->andReturn($mockCommandArray);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetSiteCommandTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'command_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('migrate');
    });

    it('handles errors', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('getCommand')
                ->with(1, 1, 999)
                ->once()
                ->andThrow(new Exception('Command not found'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetSiteCommandTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'command_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('ListDeploymentHistoryTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(ListDeploymentHistoryTool::class, []);

        $response->assertHasErrors();
    });

    it('lists deployment history successfully', function (): void {
        $mockHistory = [[
            'id' => 1,
            'status' => 'finished',
            'commit_hash' => 'abc123',
            'created_at' => '2024-01-01T00:00:00Z',
        ]];

        $this->mock(ForgeClient::class, function ($mock) use ($mockHistory): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('deploymentHistory')
                ->with(1, 1)
                ->once()
                ->andReturn($mockHistory);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ListDeploymentHistoryTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true');
    });

    it('handles errors', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('deploymentHistory')
                ->with(1, 999)
                ->once()
                ->andThrow(new Exception('Site not found'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ListDeploymentHistoryTool::class, [
            'server_id' => 1,
            'site_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('GetDeploymentHistoryDeploymentTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(GetDeploymentHistoryDeploymentTool::class, []);

        $response->assertHasErrors();
    });

    it('gets deployment details successfully', function (): void {
        $mockDeployment = [
            'id' => 1,
            'status' => 'finished',
            'commit_hash' => 'abc123',
        ];

        $this->mock(ForgeClient::class, function ($mock) use ($mockDeployment): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('deploymentHistoryDeployment')
                ->with(1, 1, 1)
                ->once()
                ->andReturn($mockDeployment);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetDeploymentHistoryDeploymentTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'deployment_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true');
    });

    it('handles errors', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('deploymentHistoryDeployment')
                ->with(1, 1, 999)
                ->once()
                ->andThrow(new Exception('Deployment not found'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetDeploymentHistoryDeploymentTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'deployment_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('GetDeploymentHistoryOutputTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(GetDeploymentHistoryOutputTool::class, []);

        $response->assertHasErrors();
    });

    it('gets deployment output successfully', function (): void {
        $mockOutput = ['output' => "Deploying...\nCompleted successfully"];

        $this->mock(ForgeClient::class, function ($mock) use ($mockOutput): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('deploymentHistoryOutput')
                ->with(1, 1, 1)
                ->once()
                ->andReturn($mockOutput);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetDeploymentHistoryOutputTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'deployment_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Deploying');
    });

    it('handles errors', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('deploymentHistoryOutput')
                ->with(1, 1, 999)
                ->once()
                ->andThrow(new Exception('Deployment not found'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetDeploymentHistoryOutputTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'deployment_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('ListDatabaseUsersTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListDatabaseUsersTool::class, []);

        $response->assertHasErrors();
    });

    it('lists database users successfully', function (): void {
        $mockUser = DatabaseUserData::from([
            'id' => 1,
            'server_id' => 1,
            'name' => 'dbuser',
            'status' => 'created',
            'created_at' => '2024-01-01T00:00:00Z',
            'databases' => [],
        ]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockUser): void {
            $userResource = Mockery::mock(DatabaseUserResource::class);
            $collection = new App\Integrations\Forge\Data\Databases\DatabaseUserCollectionData(users: [$mockUser]);
            $userResource->shouldReceive('list')
                ->with(1)
                ->once()
                ->andReturn($collection);
            $mock->shouldReceive('databaseUsers')->once()->andReturn($userResource);
        });

        $response = ForgeServer::tool(ListDatabaseUsersTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('dbuser');
    });

    it('handles errors', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $userResource = Mockery::mock(DatabaseUserResource::class);
            $userResource->shouldReceive('list')
                ->with(999)
                ->once()
                ->andThrow(new Exception('Server not found'));
            $mock->shouldReceive('databaseUsers')->once()->andReturn($userResource);
        });

        $response = ForgeServer::tool(ListDatabaseUsersTool::class, [
            'server_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('GetDatabaseUserTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(GetDatabaseUserTool::class, []);

        $response->assertHasErrors();
    });

    it('gets database user successfully', function (): void {
        $mockUser = DatabaseUserData::from([
            'id' => 1,
            'server_id' => 1,
            'name' => 'dbuser',
            'status' => 'created',
            'created_at' => '2024-01-01T00:00:00Z',
            'databases' => [],
        ]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockUser): void {
            $userResource = Mockery::mock(DatabaseUserResource::class);
            $userResource->shouldReceive('get')
                ->with(1, 1)
                ->once()
                ->andReturn($mockUser);
            $mock->shouldReceive('databaseUsers')->once()->andReturn($userResource);
        });

        $response = ForgeServer::tool(GetDatabaseUserTool::class, [
            'server_id' => 1,
            'user_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('dbuser');
    });

    it('handles errors', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $userResource = Mockery::mock(DatabaseUserResource::class);
            $userResource->shouldReceive('get')
                ->with(1, 999)
                ->once()
                ->andThrow(new Exception('User not found'));
            $mock->shouldReceive('databaseUsers')->once()->andReturn($userResource);
        });

        $response = ForgeServer::tool(GetDatabaseUserTool::class, [
            'server_id' => 1,
            'user_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('ExecuteSiteCommandTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(ExecuteSiteCommandTool::class, []);

        $response->assertHasErrors();
    });

    it('executes command successfully', function (): void {
        $mockCommandArray = [
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'command' => 'php artisan cache:clear',
            'status' => 'pending',
            'output' => null,
            'created_at' => '2024-01-01T00:00:00Z',
        ];

        $this->mock(ForgeClient::class, function ($mock) use ($mockCommandArray): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('executeCommand')
                ->with(1, 1, Mockery::type(App\Integrations\Forge\Data\Sites\ExecuteSiteCommandData::class))
                ->once()
                ->andReturn($mockCommandArray);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ExecuteSiteCommandTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'command' => 'php artisan cache:clear',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('php artisan cache:clear')
            ->assertSee('pending');
    });

    it('handles command execution error', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('executeCommand')
                ->with(1, 1, Mockery::type(App\Integrations\Forge\Data\Sites\ExecuteSiteCommandData::class))
                ->once()
                ->andThrow(new Exception('Command execution failed'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ExecuteSiteCommandTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'command' => 'invalid-command',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});
