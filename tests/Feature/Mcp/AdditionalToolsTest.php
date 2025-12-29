<?php

declare(strict_types=1);

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Resources\{CertificateResource, DatabaseResource, JobResource, ServerResource, ServiceResource, SiteResource, WorkerResource};
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Certificates\ActivateCertificateTool;
use App\Mcp\Tools\Databases\SyncDatabaseTool;
use App\Mcp\Tools\Deployments\{ResetDeploymentStateTool, SetDeploymentFailureEmailsTool};
use App\Mcp\Tools\Jobs\GetJobOutputTool;
use App\Mcp\Tools\Servers\{GetEventOutputTool, GetServerLogTool, ListEventsTool, ReactivateServerTool, ReconnectServerTool, RevokeServerAccessTool, UpdateDatabasePasswordTool};
use App\Mcp\Tools\Services\{RestartServiceTool, StartServiceTool, StopServiceTool};
use App\Mcp\Tools\Sites\{GetPackagesAuthTool, InstallPhpMyAdminTool, InstallWordPressTool, UninstallPhpMyAdminTool, UninstallWordPressTool, UpdatePackagesAuthTool};
use App\Mcp\Tools\Workers\GetWorkerOutputTool;

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

describe('UpdateDatabasePasswordTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(UpdateDatabasePasswordTool::class, []);
        $response->assertHasErrors();
    });

    it('updates database password successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('updateDatabasePassword')
                ->with(1)
                ->once();
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(UpdateDatabasePasswordTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('regenerated');
    });

    it('handles errors', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('updateDatabasePassword')
                ->with(999)
                ->once()
                ->andThrow(new Exception('Server not found'));
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(UpdateDatabasePasswordTool::class, [
            'server_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('RevokeServerAccessTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(RevokeServerAccessTool::class, []);
        $response->assertHasErrors();
    });

    it('revokes access successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('revokeAccess')
                ->with(1)
                ->once();
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(RevokeServerAccessTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('revoked');
    });
});

describe('ReconnectServerTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ReconnectServerTool::class, []);
        $response->assertHasErrors();
    });

    it('reconnects server successfully', function (): void {
        $mockKey = 'ssh-rsa AAAAB3NzaC1yc2EAAAA...';

        $this->mock(ForgeClient::class, function ($mock) use ($mockKey): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('reconnect')
                ->with(1)
                ->once()
                ->andReturn($mockKey);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ReconnectServerTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('ssh-rsa');
    });
});

describe('ReactivateServerTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ReactivateServerTool::class, []);
        $response->assertHasErrors();
    });

    it('reactivates server successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('reactivate')
                ->with(1)
                ->once();
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ReactivateServerTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('reactivated');
    });
});

describe('GetServerLogTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(GetServerLogTool::class, []);
        $response->assertHasErrors();
    });

    it('gets server log successfully', function (): void {
        $mockLog = "Jan 1 00:00:00 server sshd[1234]: Accepted publickey for forge";

        $this->mock(ForgeClient::class, function ($mock) use ($mockLog): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('getLog')
                ->with(1, 'auth')
                ->once()
                ->andReturn($mockLog);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(GetServerLogTool::class, [
            'server_id' => 1,
            'file' => 'auth',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('sshd');
    });
});

describe('ListEventsTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListEventsTool::class, []);
        $response->assertHasErrors();
    });

    it('lists events successfully', function (): void {
        $mockEvents = [
            ['id' => 1, 'description' => 'Site deployed', 'created_at' => '2024-01-01T00:00:00Z'],
        ];

        $this->mock(ForgeClient::class, function ($mock) use ($mockEvents): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('listEvents')
                ->with(1)
                ->once()
                ->andReturn($mockEvents);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListEventsTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Site deployed');
    });
});

describe('GetEventOutputTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(GetEventOutputTool::class, []);
        $response->assertHasErrors();
    });

    it('gets event output successfully', function (): void {
        $mockOutput = "Deploying site...\nCompleted successfully";

        $this->mock(ForgeClient::class, function ($mock) use ($mockOutput): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('getEventOutput')
                ->with(1, 1)
                ->once()
                ->andReturn($mockOutput);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(GetEventOutputTool::class, [
            'server_id' => 1,
            'event_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Deploying');
    });
});

describe('InstallWordPressTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(InstallWordPressTool::class, []);
        $response->assertHasErrors();
    });

    it('installs WordPress successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('installWordPress')
                ->with(1, 1, 'wordpress_db', 'wp_user', null)
                ->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(InstallWordPressTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'database' => 'wordpress_db',
            'user' => 'wp_user',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('WordPress installed');
    });
});

describe('UninstallWordPressTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(UninstallWordPressTool::class, []);
        $response->assertHasErrors();
    });

    it('uninstalls WordPress successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('uninstallWordPress')
                ->with(1, 1)
                ->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UninstallWordPressTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('uninstalled');
    });
});

describe('InstallPhpMyAdminTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(InstallPhpMyAdminTool::class, []);
        $response->assertHasErrors();
    });

    it('installs phpMyAdmin successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('installPhpMyAdmin')
                ->with(1, 1)
                ->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(InstallPhpMyAdminTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('phpMyAdmin installed');
    });
});

describe('UninstallPhpMyAdminTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(UninstallPhpMyAdminTool::class, []);
        $response->assertHasErrors();
    });

    it('uninstalls phpMyAdmin successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('uninstallPhpMyAdmin')
                ->with(1, 1)
                ->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UninstallPhpMyAdminTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('uninstalled');
    });
});

describe('GetPackagesAuthTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(GetPackagesAuthTool::class, []);
        $response->assertHasErrors();
    });

    it('gets packages auth successfully', function (): void {
        $mockPackages = [
            'github-oauth' => ['github.com' => 'token123'],
        ];

        $this->mock(ForgeClient::class, function ($mock) use ($mockPackages): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('getPackagesAuth')
                ->with(1, 1)
                ->once()
                ->andReturn($mockPackages);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetPackagesAuthTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('github-oauth');
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
            $siteResource->shouldReceive('updatePackagesAuth')
                ->with(1, 1, Mockery::type('array'))
                ->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UpdatePackagesAuthTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'packages' => ['github-oauth' => ['github.com' => 'new-token']],
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('updated');
    });
});

describe('ResetDeploymentStateTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(ResetDeploymentStateTool::class, []);
        $response->assertHasErrors();
    });

    it('resets deployment state successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('resetDeploymentState')
                ->with(1, 1)
                ->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ResetDeploymentStateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('reset');
    });
});

describe('SetDeploymentFailureEmailsTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(SetDeploymentFailureEmailsTool::class, []);
        $response->assertHasErrors();
    });

    it('sets deployment failure emails successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('setDeploymentFailureEmails')
                ->with(1, 1, ['dev@example.com', 'ops@example.com'])
                ->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(SetDeploymentFailureEmailsTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'emails' => ['dev@example.com', 'ops@example.com'],
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('dev@example.com');
    });
});

describe('StartServiceTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(StartServiceTool::class, []);
        $response->assertHasErrors();
    });

    it('starts service successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('startService')
                ->with(1, 'nginx')
                ->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(StartServiceTool::class, [
            'server_id' => 1,
            'service' => 'nginx',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('nginx')
            ->assertSee('started');
    });
});

describe('StopServiceTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(StopServiceTool::class, []);
        $response->assertHasErrors();
    });

    it('stops service successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('stopService')
                ->with(1, 'mysql')
                ->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(StopServiceTool::class, [
            'server_id' => 1,
            'service' => 'mysql',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('mysql')
            ->assertSee('stopped');
    });
});

describe('RestartServiceTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(RestartServiceTool::class, []);
        $response->assertHasErrors();
    });

    it('restarts service successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('restartService')
                ->with(1, 'php8.4')
                ->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RestartServiceTool::class, [
            'server_id' => 1,
            'service' => 'php8.4',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('php8.4')
            ->assertSee('restarted');
    });
});

describe('GetJobOutputTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(GetJobOutputTool::class, []);
        $response->assertHasErrors();
    });

    it('gets job output successfully', function (): void {
        $mockOutput = "Running scheduled task...\nCompleted";

        $this->mock(ForgeClient::class, function ($mock) use ($mockOutput): void {
            $jobResource = Mockery::mock(JobResource::class);
            $jobResource->shouldReceive('getOutput')
                ->with(1, 1)
                ->once()
                ->andReturn($mockOutput);
            $mock->shouldReceive('jobs')->once()->andReturn($jobResource);
        });

        $response = ForgeServer::tool(GetJobOutputTool::class, [
            'server_id' => 1,
            'job_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('scheduled task');
    });
});

describe('GetWorkerOutputTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(GetWorkerOutputTool::class, []);
        $response->assertHasErrors();
    });

    it('gets worker output successfully', function (): void {
        $mockOutput = "Processing: App\\Jobs\\SendEmail\nProcessed";

        $this->mock(ForgeClient::class, function ($mock) use ($mockOutput): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('getOutput')
                ->with(1, 1, 1)
                ->once()
                ->andReturn($mockOutput);
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(GetWorkerOutputTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'worker_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('SendEmail');
    });
});

describe('ActivateCertificateTool', function (): void {
    it('requires all parameters', function (): void {
        $response = ForgeServer::tool(ActivateCertificateTool::class, []);
        $response->assertHasErrors();
    });

    it('activates certificate successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('activate')
                ->with(1, 1, 1)
                ->once();
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ActivateCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'certificate_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('activated');
    });
});

describe('SyncDatabaseTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(SyncDatabaseTool::class, []);
        $response->assertHasErrors();
    });

    it('syncs databases successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $dbResource = Mockery::mock(DatabaseResource::class);
            $dbResource->shouldReceive('sync')
                ->with(1)
                ->once();
            $mock->shouldReceive('databases')->once()->andReturn($dbResource);
        });

        $response = ForgeServer::tool(SyncDatabaseTool::class, [
            'server_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('synced');
    });
});
