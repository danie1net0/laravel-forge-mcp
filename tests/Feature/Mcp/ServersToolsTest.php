<?php

declare(strict_types=1);

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Resources\ServerResource;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Servers\{CreateServerTool, DeleteServerTool, GetEventOutputTool, GetServerTool, ListEventsTool, ListServersTool, PowerCycleServerTool, RebootServerTool, UpdateDatabasePasswordTool};
use App\Integrations\Forge\Data\Servers\{ServerCollectionData, ServerData};

beforeEach(function (): void {
    config([
        'services.forge.api_token' => 'test-token',
        'services.forge.organization' => 'test-org',
    ]);
});

function createMockServer(int $id = 1, string $name = 'test-server', bool $ready = true): ServerData
{
    return ServerData::from([
        'id' => $id,
        'credential_id' => 1,
        'name' => $name,
        'type' => 'app',
        'provider' => 'ocean',
        'identifier' => "test-{$id}",
        'size' => '1gb',
        'region' => 'nyc1',
        'ubuntu_version' => '22.04',
        'db_status' => null,
        'redis_status' => null,
        'php_version' => '8.2',
        'php_cli_version' => '8.2',
        'opcache_status' => 'enabled',
        'database_type' => 'mysql8',
        'ip_address' => '192.168.1.1',
        'ssh_port' => 22,
        'private_ip_address' => '10.0.0.1',
        'local_public_key' => 'ssh-rsa...',
        'blackfire_status' => null,
        'papertrail_status' => null,
        'revoked' => false,
        'created_at' => '2024-01-01T00:00:00Z',
        'is_ready' => $ready,
        'tags' => [],
        'network' => [],
    ]);
}

describe('ListServersTool', function (): void {
    it('lists servers successfully', function (): void {
        $mockServer = createMockServer();

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockServer): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $collection = new ServerCollectionData(servers: [$mockServer]);
            $serverResource->shouldReceive('list')->once()->andReturn($collection);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response->assertOk()->assertSee('test-server');
    });

    it('returns empty list when no servers exist', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $collection = new ServerCollectionData(servers: []);
            $serverResource->shouldReceive('list')->once()->andReturn($collection);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response->assertOk()->assertSee('"count": 0');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('list')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListServersTool::class, []);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('GetServerTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(GetServerTool::class, []);

        $response->assertHasErrors();
    });

    it('rejects invalid server_id', function (): void {
        $response = ForgeServer::tool(GetServerTool::class, ['server_id' => 'invalid']);

        $response->assertHasErrors();
    });

    it('rejects zero server_id', function (): void {
        $response = ForgeServer::tool(GetServerTool::class, ['server_id' => 0]);

        $response->assertHasErrors();
    });

    it('rejects negative server_id', function (): void {
        $response = ForgeServer::tool(GetServerTool::class, ['server_id' => -1]);

        $response->assertHasErrors();
    });

    it('gets server details successfully', function (): void {
        $mockServer = createMockServer();

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockServer): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('get')->with(Mockery::any())->once()->andReturn($mockServer);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(GetServerTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('test-server')->assertSee('192.168.1.1');
    });

    it('handles server not found', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('get')->andThrow(new Exception('Server not found'));
            $mock->shouldReceive('servers')->andReturn($serverResource);
        });

        $response = ForgeServer::tool(GetServerTool::class, ['server_id' => 999]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('CreateServerTool', function (): void {
    it('requires mandatory parameters', function (): void {
        $response = ForgeServer::tool(CreateServerTool::class, []);

        $response->assertHasErrors();
    });

    it('requires name parameter', function (): void {
        $response = ForgeServer::tool(CreateServerTool::class, [
            'provider' => 'ocean2',
            'type' => 'app',
            'ubuntu_version' => '24.04',
        ]);

        $response->assertHasErrors();
    });

    it('creates server successfully', function (): void {
        $mockServer = createMockServer();

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockServer): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('create')->once()->andReturn($mockServer);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(CreateServerTool::class, [
            'name' => 'new-server',
            'provider' => 'ocean2',
            'type' => 'app',
            'ubuntu_version' => '24.04',
            'region_id' => 'nyc1',
            'size_id' => 's-1vcpu-1gb',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('DeleteServerTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(DeleteServerTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes server successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('delete')->with(1)->once();
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(DeleteServerTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('RebootServerTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(RebootServerTool::class, []);

        $response->assertHasErrors();
    });

    it('reboots server successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('reboot')->with(1)->once();
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(RebootServerTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('PowerCycleServerTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(PowerCycleServerTool::class, []);

        $response->assertHasErrors();
    });

    it('power cycles server successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('powerCycle')->with(1)->once();
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(PowerCycleServerTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('ListEventsTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListEventsTool::class, []);

        $response->assertHasErrors();
    });

    it('lists events successfully', function (): void {
        $mockEvents = [
            [
                'id' => 1,
                'server_id' => 1,
                'description' => 'Server rebooted',
                'status' => 'success',
                'created_at' => '2024-01-01T00:00:00Z',
            ],
        ];

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockEvents): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('listEvents')->with(1, null, 30)->once()->andReturn($mockEvents);
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(ListEventsTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('Server rebooted');
    });
});

describe('GetEventOutputTool', function (): void {
    it('requires server_id and event_id parameters', function (): void {
        $response = ForgeServer::tool(GetEventOutputTool::class, []);

        $response->assertHasErrors();
    });

    it('gets event output successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('getEventOutput')->with(1, 1)->once()->andReturn('Event output content');
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(GetEventOutputTool::class, [
            'server_id' => 1,
            'event_id' => 1,
        ]);

        $response->assertOk()->assertSee('Event output content');
    });
});

describe('UpdateDatabasePasswordTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(UpdateDatabasePasswordTool::class, []);

        $response->assertHasErrors();
    });

    it('updates database password successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $serverResource = Mockery::mock(ServerResource::class);
            $serverResource->shouldReceive('updateDatabasePassword')->with(1)->once();
            $mock->shouldReceive('servers')->once()->andReturn($serverResource);
        });

        $response = ForgeServer::tool(UpdateDatabasePasswordTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('Server Tools Structure', function (): void {
    it('all server tools can be instantiated', function (): void {
        $tools = [
            ListServersTool::class,
            GetServerTool::class,
            CreateServerTool::class,
            DeleteServerTool::class,
            RebootServerTool::class,
            PowerCycleServerTool::class,
            ListEventsTool::class,
            GetEventOutputTool::class,
            UpdateDatabasePasswordTool::class,
        ];

        foreach ($tools as $toolClass) {
            $tool = app($toolClass);
            expect($tool->name())->toBeString()->not->toBeEmpty();
            expect($tool->description())->toBeString()->not->toBeEmpty();
        }
    });
});
