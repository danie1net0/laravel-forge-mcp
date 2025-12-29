<?php

declare(strict_types=1);

use App\Integrations\Forge\ForgeClient;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Jobs\{CreateScheduledJobTool, DeleteScheduledJobTool, GetJobOutputTool, GetScheduledJobTool, ListScheduledJobsTool};
use App\Mcp\Tools\Daemons\{CreateDaemonTool, DeleteDaemonTool, GetDaemonTool, ListDaemonsTool, RestartDaemonTool};
use App\Mcp\Tools\Workers\{CreateWorkerTool, DeleteWorkerTool, GetWorkerOutputTool, GetWorkerTool, ListWorkersTool, RestartWorkerTool};
use App\Integrations\Forge\Data\Jobs\{CreateJobData, JobCollectionData, JobData};
use App\Integrations\Forge\Resources\{DaemonResource, JobResource, WorkerResource};
use App\Integrations\Forge\Data\Daemons\{CreateDaemonData, DaemonCollectionData, DaemonData};
use App\Integrations\Forge\Data\Workers\{CreateWorkerData, WorkerCollectionData, WorkerData};

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

function createMockWorker(int $id = 1): WorkerData
{
    return WorkerData::from([
        'id' => $id,
        'server_id' => 1,
        'site_id' => 1,
        'connection' => 'redis',
        'command' => 'php artisan queue:work',
        'queue' => 'default',
        'timeout' => 60,
        'sleep' => 3,
        'tries' => 1,
        'environment' => 'production',
        'daemon' => 1,
        'status' => 'installed',
        'created_at' => '2024-01-01T00:00:00Z',
    ]);
}

function createMockJob(int $id = 1): JobData
{
    return JobData::from([
        'id' => $id,
        'server_id' => 1,
        'command' => 'php artisan schedule:run',
        'user' => 'forge',
        'frequency' => 'minutely',
        'cron' => '* * * * *',
        'status' => 'installed',
        'created_at' => '2024-01-01T00:00:00Z',
    ]);
}

function createMockDaemon(int $id = 1): DaemonData
{
    return DaemonData::from([
        'id' => $id,
        'server_id' => 1,
        'command' => 'node server.js',
        'user' => 'forge',
        'status' => 'installed',
        'directory' => '/home/forge/app',
        'created_at' => '2024-01-01T00:00:00Z',
    ]);
}

describe('ListWorkersTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(ListWorkersTool::class, []);

        $response->assertHasErrors();
    });

    it('lists workers successfully', function (): void {
        $mockWorker = createMockWorker();
        $collection = new WorkerCollectionData(workers: [$mockWorker]);

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('list')->with(1, 1)->once()->andReturn($collection);
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(ListWorkersTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('redis');
    });
});

describe('GetWorkerTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(GetWorkerTool::class, []);

        $response->assertHasErrors();
    });

    it('gets worker details successfully', function (): void {
        $mockWorker = createMockWorker();

        $this->mock(ForgeClient::class, function ($mock) use ($mockWorker): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('get')->with(1, 1, 1)->once()->andReturn($mockWorker);
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(GetWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'worker_id' => 1,
        ]);

        $response->assertOk()->assertSee('redis');
    });
});

describe('CreateWorkerTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(CreateWorkerTool::class, []);

        $response->assertHasErrors();
    });

    it('requires connection and queue parameters', function (): void {
        $response = ForgeServer::tool(CreateWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('creates worker successfully', function (): void {
        $mockWorker = createMockWorker();

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

        $response->assertOk()->assertSee('"success": true');
    });

    it('creates worker with optional parameters', function (): void {
        $mockWorker = createMockWorker();

        $this->mock(ForgeClient::class, function ($mock) use ($mockWorker): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('create')->once()->andReturn($mockWorker);
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(CreateWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'connection' => 'redis',
            'queue' => 'high,default,low',
            'timeout' => 300,
            'sleep' => 5,
            'tries' => 3,
            'daemon' => true,
            'force' => true,
        ]);

        $response->assertOk();
    });
});

describe('RestartWorkerTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(RestartWorkerTool::class, []);

        $response->assertHasErrors();
    });

    it('restarts worker successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('restart')->with(1, 1, 1)->once();
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(RestartWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'worker_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('DeleteWorkerTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(DeleteWorkerTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes worker successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('delete')->with(1, 1, 1)->once();
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(DeleteWorkerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'worker_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('GetWorkerOutputTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(GetWorkerOutputTool::class, []);

        $response->assertHasErrors();
    });

    it('gets worker output successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $workerResource = Mockery::mock(WorkerResource::class);
            $workerResource->shouldReceive('getOutput')->with(1, 1, 1)->once()->andReturn('Worker output log');
            $mock->shouldReceive('workers')->once()->andReturn($workerResource);
        });

        $response = ForgeServer::tool(GetWorkerOutputTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'worker_id' => 1,
        ]);

        $response->assertOk()->assertSee('Worker output log');
    });
});

describe('ListScheduledJobsTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListScheduledJobsTool::class, []);

        $response->assertHasErrors();
    });

    it('lists scheduled jobs successfully', function (): void {
        $mockJob = createMockJob();
        $collection = new JobCollectionData(jobs: [$mockJob]);

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $jobResource = Mockery::mock(JobResource::class);
            $jobResource->shouldReceive('list')->with(1)->once()->andReturn($collection);
            $mock->shouldReceive('jobs')->once()->andReturn($jobResource);
        });

        $response = ForgeServer::tool(ListScheduledJobsTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('schedule:run');
    });
});

describe('GetScheduledJobTool', function (): void {
    it('requires server_id and job_id parameters', function (): void {
        $response = ForgeServer::tool(GetScheduledJobTool::class, []);

        $response->assertHasErrors();
    });

    it('gets scheduled job details successfully', function (): void {
        $mockJob = createMockJob();

        $this->mock(ForgeClient::class, function ($mock) use ($mockJob): void {
            $jobResource = Mockery::mock(JobResource::class);
            $jobResource->shouldReceive('get')->with(1, 1)->once()->andReturn($mockJob);
            $mock->shouldReceive('jobs')->once()->andReturn($jobResource);
        });

        $response = ForgeServer::tool(GetScheduledJobTool::class, [
            'server_id' => 1,
            'job_id' => 1,
        ]);

        $response->assertOk()->assertSee('schedule:run');
    });
});

describe('CreateScheduledJobTool', function (): void {
    it('requires mandatory parameters', function (): void {
        $response = ForgeServer::tool(CreateScheduledJobTool::class, []);

        $response->assertHasErrors();
    });

    it('requires command and frequency parameters', function (): void {
        $response = ForgeServer::tool(CreateScheduledJobTool::class, [
            'server_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('creates scheduled job successfully', function (): void {
        $mockJob = createMockJob();

        $this->mock(ForgeClient::class, function ($mock) use ($mockJob): void {
            $jobResource = Mockery::mock(JobResource::class);
            $jobResource->shouldReceive('create')
                ->with(1, Mockery::type(CreateJobData::class))
                ->once()
                ->andReturn($mockJob);
            $mock->shouldReceive('jobs')->once()->andReturn($jobResource);
        });

        $response = ForgeServer::tool(CreateScheduledJobTool::class, [
            'server_id' => 1,
            'command' => 'php artisan schedule:run',
            'frequency' => 'minutely',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('DeleteScheduledJobTool', function (): void {
    it('requires server_id and job_id parameters', function (): void {
        $response = ForgeServer::tool(DeleteScheduledJobTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes scheduled job successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $jobResource = Mockery::mock(JobResource::class);
            $jobResource->shouldReceive('delete')->with(1, 1)->once();
            $mock->shouldReceive('jobs')->once()->andReturn($jobResource);
        });

        $response = ForgeServer::tool(DeleteScheduledJobTool::class, [
            'server_id' => 1,
            'job_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('GetJobOutputTool', function (): void {
    it('requires server_id and job_id parameters', function (): void {
        $response = ForgeServer::tool(GetJobOutputTool::class, []);

        $response->assertHasErrors();
    });

    it('gets job output successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $jobResource = Mockery::mock(JobResource::class);
            $jobResource->shouldReceive('getOutput')->with(1, 1)->once()->andReturn('Job output log');
            $mock->shouldReceive('jobs')->once()->andReturn($jobResource);
        });

        $response = ForgeServer::tool(GetJobOutputTool::class, [
            'server_id' => 1,
            'job_id' => 1,
        ]);

        $response->assertOk()->assertSee('Job output log');
    });
});

describe('ListDaemonsTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListDaemonsTool::class, []);

        $response->assertHasErrors();
    });

    it('lists daemons successfully', function (): void {
        $mockDaemon = createMockDaemon();
        $collection = new DaemonCollectionData(daemons: [$mockDaemon]);

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('list')->with(1)->once()->andReturn($collection);
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(ListDaemonsTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('node server.js');
    });
});

describe('GetDaemonTool', function (): void {
    it('requires server_id and daemon_id parameters', function (): void {
        $response = ForgeServer::tool(GetDaemonTool::class, []);

        $response->assertHasErrors();
    });

    it('gets daemon details successfully', function (): void {
        $mockDaemon = createMockDaemon();

        $this->mock(ForgeClient::class, function ($mock) use ($mockDaemon): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('get')->with(1, 1)->once()->andReturn($mockDaemon);
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(GetDaemonTool::class, [
            'server_id' => 1,
            'daemon_id' => 1,
        ]);

        $response->assertOk()->assertSee('node server.js');
    });
});

describe('CreateDaemonTool', function (): void {
    it('requires mandatory parameters', function (): void {
        $response = ForgeServer::tool(CreateDaemonTool::class, []);

        $response->assertHasErrors();
    });

    it('creates daemon successfully', function (): void {
        $mockDaemon = createMockDaemon();

        $this->mock(ForgeClient::class, function ($mock) use ($mockDaemon): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('create')
                ->with(1, Mockery::type(CreateDaemonData::class))
                ->once()
                ->andReturn($mockDaemon);
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(CreateDaemonTool::class, [
            'server_id' => 1,
            'command' => 'node server.js',
            'directory' => '/home/forge/app',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('RestartDaemonTool', function (): void {
    it('requires server_id and daemon_id parameters', function (): void {
        $response = ForgeServer::tool(RestartDaemonTool::class, []);

        $response->assertHasErrors();
    });

    it('restarts daemon successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('restart')->with(1, 1)->once();
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(RestartDaemonTool::class, [
            'server_id' => 1,
            'daemon_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('DeleteDaemonTool', function (): void {
    it('requires server_id and daemon_id parameters', function (): void {
        $response = ForgeServer::tool(DeleteDaemonTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes daemon successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('delete')->with(1, 1)->once();
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(DeleteDaemonTool::class, [
            'server_id' => 1,
            'daemon_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('Workers/Jobs/Daemons Tools Structure', function (): void {
    it('all worker tools can be instantiated', function (): void {
        $tools = [
            ListWorkersTool::class,
            GetWorkerTool::class,
            CreateWorkerTool::class,
            RestartWorkerTool::class,
            DeleteWorkerTool::class,
            GetWorkerOutputTool::class,
        ];

        foreach ($tools as $toolClass) {
            $tool = app($toolClass);
            expect($tool->name())->toBeString()->not->toBeEmpty();
        }
    });

    it('all job tools can be instantiated', function (): void {
        $tools = [
            ListScheduledJobsTool::class,
            GetScheduledJobTool::class,
            CreateScheduledJobTool::class,
            DeleteScheduledJobTool::class,
            GetJobOutputTool::class,
        ];

        foreach ($tools as $toolClass) {
            $tool = app($toolClass);
            expect($tool->name())->toBeString()->not->toBeEmpty();
        }
    });

    it('all daemon tools can be instantiated', function (): void {
        $tools = [
            ListDaemonsTool::class,
            GetDaemonTool::class,
            CreateDaemonTool::class,
            RestartDaemonTool::class,
            DeleteDaemonTool::class,
        ];

        foreach ($tools as $toolClass) {
            $tool = app($toolClass);
            expect($tool->name())->toBeString()->not->toBeEmpty();
        }
    });
});
