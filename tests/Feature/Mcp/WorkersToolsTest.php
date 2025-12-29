<?php

declare(strict_types=1);

use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Workers\{CreateWorkerTool, DeleteWorkerTool, GetWorkerTool, ListWorkersTool, RestartWorkerTool};
use App\Integrations\Forge\Data\Workers\{CreateWorkerData, WorkerCollectionData, WorkerData};
use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Resources\WorkerResource;

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

function createTestWorker(int $id, int $serverId, int $siteId, string $connection, string $queue, int $timeout = 60, int $sleep = 3, int $tries = 1, int $daemon = 1, string $status = 'installed', string $environment = 'production'): WorkerData
{
    return WorkerData::from([
        'id' => $id,
        'server_id' => $serverId,
        'site_id' => $siteId,
        'connection' => $connection,
        'command' => "php artisan queue:work {$connection} --queue={$queue}",
        'queue' => $queue,
        'timeout' => $timeout,
        'sleep' => $sleep,
        'tries' => $tries,
        'environment' => $environment,
        'daemon' => $daemon,
        'status' => $status,
        'created_at' => '2024-01-01T00:00:00Z',
    ]);
}

describe('ListWorkersTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(ListWorkersTool::class, []);

        $response->assertHasErrors();
    });

    it('requires site_id when server_id is provided', function (): void {
        $response = ForgeServer::tool(ListWorkersTool::class, [
            'server_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('lists workers successfully', function (): void {
        $mockWorker = createTestWorker(1, 1, 1, 'redis', 'default');

        $this->mock(ForgeClient::class, function ($mock) use ($mockWorker): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $collection = new WorkerCollectionData(workers: [$mockWorker]);
            $workerResource->shouldReceive('list')->with(Mockery::any(), Mockery::any())->once()->andReturn($collection);
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(ListWorkersTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('redis')
            ->assertSee('default')
            ->assertSee('installed');
    });

    it('handles empty workers list', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $collection = new WorkerCollectionData(workers: []);
            $workerResource->shouldReceive('list')->with(Mockery::any(), Mockery::any())->once()->andReturn($collection);
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(ListWorkersTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"count": 0');
    });

    it('handles API errors', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('list')->with(Mockery::any(), Mockery::any())->once()->andThrow(new Exception('Site not found'));
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(ListWorkersTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Site not found');
    });
});

describe('GetWorkerTool', function (): void {
    it('requires server_id, site_id and worker_id parameters', function (): void {
        $response = ForgeServer::tool(GetWorkerTool::class, []);

        $response->assertHasErrors();
    });

    it('requires worker_id when server_id and site_id provided', function (): void {
        $response = ForgeServer::tool(GetWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('gets worker details successfully', function (): void {
        $mockWorker = createTestWorker(1, 1, 1, 'redis', 'high,default', 120, 5, 3);

        $this->mock(ForgeClient::class, function ($mock) use ($mockWorker): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('get')->with(Mockery::any(), Mockery::any(), Mockery::any())->once()->andReturn($mockWorker);
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(GetWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'worker_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('redis')
            ->assertSee('high,default')
            ->assertSee('"timeout": 120');
    });

    it('handles worker not found error', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('get')->with(Mockery::any(), Mockery::any(), Mockery::any())->once()->andThrow(new Exception('Worker not found'));
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(GetWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'worker_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Worker not found');
    });
});

describe('CreateWorkerTool', function (): void {
    it('requires server_id, site_id, connection and queue parameters', function (): void {
        $response = ForgeServer::tool(CreateWorkerTool::class, []);

        $response->assertHasErrors();
    });

    it('requires queue when other required params provided', function (): void {
        $response = ForgeServer::tool(CreateWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'connection' => 'redis',
        ]);

        $response->assertHasErrors();
    });

    it('creates basic worker successfully', function (): void {
        $mockWorker = createTestWorker(1, 1, 1, 'redis', 'default', 60, 3, 1, 1, 'installing');

        $this->mock(ForgeClient::class, function ($mock) use ($mockWorker): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('create')
                ->with(1, 1, Mockery::type(CreateWorkerData::class))
                ->once()
                ->andReturn($mockWorker);
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(CreateWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'connection' => 'redis',
            'queue' => 'default',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('installing');
    });

    it('creates worker with custom settings', function (): void {
        $mockWorker = createTestWorker(2, 1, 1, 'database', 'emails', 300, 10, 5, 0, 'installing');

        $this->mock(ForgeClient::class, function ($mock) use ($mockWorker): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('create')
                ->with(1, 1, Mockery::type(CreateWorkerData::class))
                ->once()
                ->andReturn($mockWorker);
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(CreateWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'connection' => 'database',
            'queue' => 'emails',
            'timeout' => 300,
            'sleep' => 10,
            'tries' => 5,
            'daemon' => false,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('emails');
    });

    it('handles worker creation error', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('create')
                ->with(1, 1, Mockery::type(CreateWorkerData::class))
                ->once()
                ->andThrow(new Exception('Failed to create worker'));
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(CreateWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'connection' => 'redis',
            'queue' => 'default',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Failed to create worker');
    });
});

describe('RestartWorkerTool', function (): void {
    it('requires server_id, site_id and worker_id parameters', function (): void {
        $response = ForgeServer::tool(RestartWorkerTool::class, []);

        $response->assertHasErrors();
    });

    it('restarts worker successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('restart')->with(Mockery::any(), Mockery::any(), Mockery::any())->once();
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(RestartWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'worker_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('restarted successfully');
    });

    it('handles restart error', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('restart')->with(Mockery::any(), Mockery::any(), Mockery::any())->once()->andThrow(new Exception('Worker not found'));
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(RestartWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'worker_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Worker not found');
    });
});

describe('DeleteWorkerTool', function (): void {
    it('requires server_id, site_id and worker_id parameters', function (): void {
        $response = ForgeServer::tool(DeleteWorkerTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes worker successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('delete')->with(Mockery::any(), Mockery::any(), Mockery::any())->once();
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(DeleteWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'worker_id' => 1,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('deleted successfully');
    });

    it('handles deletion error', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('delete')->with(Mockery::any(), Mockery::any(), Mockery::any())->once()->andThrow(new Exception('Worker not found'));
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(DeleteWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'worker_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Worker not found');
    });
});
