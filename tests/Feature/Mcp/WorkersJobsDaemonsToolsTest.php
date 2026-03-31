<?php

declare(strict_types=1);

use App\Integrations\Forge\ForgeClient;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Jobs\{CreateScheduledJobTool, DeleteScheduledJobTool, GetJobOutputTool, GetScheduledJobTool, ListScheduledJobsTool};
use App\Mcp\Tools\Daemons\{CreateDaemonTool, DeleteDaemonTool, GetDaemonTool, ListDaemonsTool, RestartDaemonTool};
use App\Integrations\Forge\Data\Jobs\{CreateJobData, JobCollectionData, JobData};
use App\Integrations\Forge\Resources\{DaemonResource, JobResource};
use App\Integrations\Forge\Data\Daemons\{CreateDaemonData, DaemonCollectionData, DaemonData};

beforeEach(function (): void {
    config([
        'services.forge.api_token' => 'test-token',
        'services.forge.organization' => 'test-org',
    ]);
});

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
            $jobResource->shouldReceive('list')->with(1, null, 30)->once()->andReturn($collection);
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
            $daemonResource->shouldReceive('list')->with(1, null, 30)->once()->andReturn($collection);
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
            'name' => 'test-daemon',
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

describe('Jobs/Daemons Tools Structure', function (): void {
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
