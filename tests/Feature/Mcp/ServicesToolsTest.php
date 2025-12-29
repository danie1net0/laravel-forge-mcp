<?php

declare(strict_types=1);

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Resources\ServiceResource;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Services\{InstallBlackfireTool, InstallPapertrailTool, RebootMysqlTool, RebootNginxTool, RebootPhpTool, RebootPostgresTool, RemoveBlackfireTool, RemovePapertrailTool, RestartServiceTool, StartServiceTool, StopMysqlTool, StopNginxTool, StopPostgresTool, StopServiceTool, TestNginxTool};

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

describe('RebootNginxTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(RebootNginxTool::class, []);

        $response->assertHasErrors();
    });

    it('reboots nginx successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('rebootNginx')->with(1)->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RebootNginxTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('StopNginxTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(StopNginxTool::class, []);

        $response->assertHasErrors();
    });

    it('stops nginx successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('stopNginx')->with(1)->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(StopNginxTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('TestNginxTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(TestNginxTool::class, []);

        $response->assertHasErrors();
    });

    it('tests nginx configuration successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('testNginx')->with(1)->once()->andReturn(['valid' => true]);
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(TestNginxTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('RebootMysqlTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(RebootMysqlTool::class, []);

        $response->assertHasErrors();
    });

    it('reboots mysql successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('rebootMysql')->with(1)->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RebootMysqlTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('StopMysqlTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(StopMysqlTool::class, []);

        $response->assertHasErrors();
    });

    it('stops mysql successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('stopMysql')->with(1)->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(StopMysqlTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('RebootPostgresTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(RebootPostgresTool::class, []);

        $response->assertHasErrors();
    });

    it('reboots postgres successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('rebootPostgres')->with(1)->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RebootPostgresTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('StopPostgresTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(StopPostgresTool::class, []);

        $response->assertHasErrors();
    });

    it('stops postgres successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('stopPostgres')->with(1)->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(StopPostgresTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('RebootPhpTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(RebootPhpTool::class, []);

        $response->assertHasErrors();
    });

    it('reboots php successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('rebootPhp')->with(1)->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RebootPhpTool::class, [
            'server_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('InstallBlackfireTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(InstallBlackfireTool::class, []);

        $response->assertHasErrors();
    });

    it('installs blackfire successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('installBlackfire')
                ->with(1, 'server-id', 'server-token')
                ->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(InstallBlackfireTool::class, [
            'server_id' => 1,
            'server_id_token' => 'server-id',
            'server_token' => 'server-token',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('RemoveBlackfireTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(RemoveBlackfireTool::class, []);

        $response->assertHasErrors();
    });

    it('removes blackfire successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('removeBlackfire')->with(1)->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RemoveBlackfireTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('InstallPapertrailTool', function (): void {
    it('requires server_id and host parameter', function (): void {
        $response = ForgeServer::tool(InstallPapertrailTool::class, []);

        $response->assertHasErrors();
    });

    it('installs papertrail successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('installPapertrail')
                ->with(1, 'logs.papertrailapp.com:12345')
                ->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(InstallPapertrailTool::class, [
            'server_id' => 1,
            'host' => 'logs.papertrailapp.com:12345',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('RemovePapertrailTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(RemovePapertrailTool::class, []);

        $response->assertHasErrors();
    });

    it('removes papertrail successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('removePapertrail')->with(1)->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RemovePapertrailTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('StartServiceTool', function (): void {
    it('requires server_id and service parameters', function (): void {
        $response = ForgeServer::tool(StartServiceTool::class, []);

        $response->assertHasErrors();
    });

    it('starts service successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('startService')->with(1, 'nginx')->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(StartServiceTool::class, [
            'server_id' => 1,
            'service' => 'nginx',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('StopServiceTool', function (): void {
    it('requires server_id and service parameters', function (): void {
        $response = ForgeServer::tool(StopServiceTool::class, []);

        $response->assertHasErrors();
    });

    it('stops service successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('stopService')->with(1, 'nginx')->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(StopServiceTool::class, [
            'server_id' => 1,
            'service' => 'nginx',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('RestartServiceTool', function (): void {
    it('requires server_id and service parameters', function (): void {
        $response = ForgeServer::tool(RestartServiceTool::class, []);

        $response->assertHasErrors();
    });

    it('restarts service successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $serviceResource = Mockery::mock(ServiceResource::class);
            $serviceResource->shouldReceive('restartService')->with(1, 'nginx')->once();
            $mock->shouldReceive('services')->once()->andReturn($serviceResource);
        });

        $response = ForgeServer::tool(RestartServiceTool::class, [
            'server_id' => 1,
            'service' => 'nginx',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('Services Tools Structure', function (): void {
    it('all service tools can be instantiated', function (): void {
        $tools = [
            RebootNginxTool::class,
            StopNginxTool::class,
            TestNginxTool::class,
            RebootMysqlTool::class,
            StopMysqlTool::class,
            RebootPostgresTool::class,
            StopPostgresTool::class,
            RebootPhpTool::class,
            InstallBlackfireTool::class,
            RemoveBlackfireTool::class,
            InstallPapertrailTool::class,
            RemovePapertrailTool::class,
            StartServiceTool::class,
            StopServiceTool::class,
            RestartServiceTool::class,
        ];

        foreach ($tools as $toolClass) {
            $tool = app($toolClass);
            expect($tool->name())->toBeString()->not->toBeEmpty();
            expect($tool->description())->toBeString()->not->toBeEmpty();
        }
    });
});
