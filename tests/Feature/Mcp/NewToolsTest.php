<?php

declare(strict_types=1);

use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Certificates\GetCertificateSigningRequestTool;
use App\Mcp\Tools\Commands\{GetSiteCommandTool, ListCommandHistoryTool};
use App\Mcp\Tools\Databases\{GetDatabaseUserTool, ListDatabaseUsersTool};
use App\Mcp\Tools\Deployments\{GetDeploymentHistoryDeploymentTool, GetDeploymentHistoryOutputTool, ListDeploymentHistoryTool};
use App\Services\ForgeService;
use Laravel\Forge\Resources\DatabaseUser;

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

        $this->mock(ForgeService::class, function ($mock) use ($mockCsr): void {
            $mock->shouldReceive('getCertificateSigningRequest')
                ->with(1, 1, 1)
                ->once()
                ->andReturn($mockCsr);
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
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('getCertificateSigningRequest')
                ->with(1, 1, 999)
                ->once()
                ->andThrow(new Exception('Certificate not found'));
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
            'command' => 'php artisan cache:clear',
            'status' => 'finished',
            'created_at' => '2024-01-01T00:00:00Z',
        ]];

        $this->mock(ForgeService::class, function ($mock) use ($mockCommands): void {
            $mock->shouldReceive('listCommandHistory')
                ->with(1, 1)
                ->once()
                ->andReturn($mockCommands);
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
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('listCommandHistory')
                ->with(1, 999)
                ->once()
                ->andThrow(new Exception('Site not found'));
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
        $mockCommand = [[
            'id' => 1,
            'command' => 'php artisan migrate',
            'status' => 'finished',
            'output' => 'Migration successful',
        ]];

        $this->mock(ForgeService::class, function ($mock) use ($mockCommand): void {
            $mock->shouldReceive('getSiteCommand')
                ->with(1, 1, 1)
                ->once()
                ->andReturn($mockCommand);
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
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('getSiteCommand')
                ->with(1, 1, 999)
                ->once()
                ->andThrow(new Exception('Command not found'));
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
        $mockHistory = ['deployments' => [[
            'id' => 1,
            'status' => 'finished',
            'commit_hash' => 'abc123',
            'created_at' => '2024-01-01T00:00:00Z',
        ]]];

        $this->mock(ForgeService::class, function ($mock) use ($mockHistory): void {
            $mock->shouldReceive('deploymentHistory')
                ->with(1, 1)
                ->once()
                ->andReturn($mockHistory);
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
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('deploymentHistory')
                ->with(1, 999)
                ->once()
                ->andThrow(new Exception('Site not found'));
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
        $mockDeployment = ['deployment' => [
            'id' => 1,
            'status' => 'finished',
            'commit_hash' => 'abc123',
        ]];

        $this->mock(ForgeService::class, function ($mock) use ($mockDeployment): void {
            $mock->shouldReceive('deploymentHistoryDeployment')
                ->with(1, 1, 1)
                ->once()
                ->andReturn($mockDeployment);
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
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('deploymentHistoryDeployment')
                ->with(1, 1, 999)
                ->once()
                ->andThrow(new Exception('Deployment not found'));
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

        $this->mock(ForgeService::class, function ($mock) use ($mockOutput): void {
            $mock->shouldReceive('deploymentHistoryOutput')
                ->with(1, 1, 1)
                ->once()
                ->andReturn($mockOutput);
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
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('deploymentHistoryOutput')
                ->with(1, 1, 999)
                ->once()
                ->andThrow(new Exception('Deployment not found'));
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
        $mockUsers = [
            new DatabaseUser(['id' => 1, 'name' => 'dbuser', 'status' => 'created']),
        ];

        $this->mock(ForgeService::class, function ($mock) use ($mockUsers): void {
            $mock->shouldReceive('listDatabaseUsers')
                ->with(1)
                ->once()
                ->andReturn($mockUsers);
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
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('listDatabaseUsers')
                ->with(999)
                ->once()
                ->andThrow(new Exception('Server not found'));
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
        $mockUser = new DatabaseUser([
            'id' => 1,
            'name' => 'dbuser',
            'status' => 'created',
            'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeService::class, function ($mock) use ($mockUser): void {
            $mock->shouldReceive('getDatabaseUser')
                ->with(1, 1)
                ->once()
                ->andReturn($mockUser);
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
        $this->mock(ForgeService::class, function ($mock): void {
            $mock->shouldReceive('getDatabaseUser')
                ->with(1, 999)
                ->once()
                ->andThrow(new Exception('User not found'));
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
