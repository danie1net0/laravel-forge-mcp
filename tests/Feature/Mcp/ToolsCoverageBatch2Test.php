<?php

declare(strict_types=1);

use App\Integrations\Forge\ForgeClient;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Git\{CreateDeployKeyTool, DestroyDeployKeyTool, DestroyGitRepositoryTool, InstallGitRepositoryTool, UpdateGitRepositoryTool};
use App\Mcp\Tools\Jobs\{CreateScheduledJobTool, DeleteScheduledJobTool, GetJobOutputTool, GetScheduledJobTool, ListScheduledJobsTool};
use App\Mcp\Tools\Firewall\{CreateFirewallRuleTool, DeleteFirewallRuleTool, GetFirewallRuleTool, ListFirewallRulesTool};
use App\Mcp\Tools\Databases\{CreateDatabaseTool, CreateDatabaseUserTool, DeleteDatabaseTool, DeleteDatabaseUserTool, GetDatabaseTool, GetDatabaseUserTool, ListDatabaseUsersTool, ListDatabasesTool, SyncDatabaseTool, UpdateDatabaseUserTool};
use App\Mcp\Tools\Deployments\{DeploySiteTool, DisableQuickDeployTool, EnableQuickDeployTool, GetDeploymentHistoryDeploymentTool, GetDeploymentHistoryOutputTool, GetDeploymentLogTool, GetDeploymentScriptTool, ListDeploymentHistoryTool, ResetDeploymentStateTool, SetDeploymentFailureEmailsTool, UpdateDeploymentScriptTool};
use App\Integrations\Forge\Data\Jobs\{CreateJobData, JobCollectionData, JobData};
use App\Integrations\Forge\Resources\{DatabaseResource, DatabaseUserResource, FirewallResource, JobResource, SiteResource};
use App\Integrations\Forge\Data\Sites\{InstallGitRepositoryData, SiteData, UpdateGitRepositoryData};
use App\Integrations\Forge\Data\Firewall\{CreateFirewallRuleData, FirewallRuleCollectionData, FirewallRuleData};
use App\Integrations\Forge\Data\Databases\{CreateDatabaseData, CreateDatabaseUserData, DatabaseCollectionData, DatabaseData, DatabaseUserCollectionData, DatabaseUserData, UpdateDatabaseUserData};

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

// ============================================================
// Databases
// ============================================================

describe('ListDatabasesTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListDatabasesTool::class, []);

        $response->assertHasErrors();
    });

    it('lists databases successfully', function (): void {
        $database = DatabaseData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'forge',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($database): void {
            $resource = Mockery::mock(DatabaseResource::class);
            $resource->shouldReceive('list')->with(1)->once()->andReturn(new DatabaseCollectionData(databases: [$database]));
            $mock->shouldReceive('databases')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListDatabasesTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('forge');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(DatabaseResource::class);
            $resource->shouldReceive('list')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('databases')->andReturn($resource);
        });

        $response = ForgeServer::tool(ListDatabasesTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });

    it('returns empty list when no databases exist', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(DatabaseResource::class);
            $resource->shouldReceive('list')->once()->andReturn(new DatabaseCollectionData(databases: []));
            $mock->shouldReceive('databases')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListDatabasesTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"count": 0');
    });
});

describe('GetDatabaseTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetDatabaseTool::class, []);

        $response->assertHasErrors();
    });

    it('gets database details successfully', function (): void {
        $database = DatabaseData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'forge',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($database): void {
            $resource = Mockery::mock(DatabaseResource::class);
            $resource->shouldReceive('get')->with(1, 1)->once()->andReturn($database);
            $mock->shouldReceive('databases')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetDatabaseTool::class, ['server_id' => 1, 'database_id' => 1]);

        $response->assertOk()->assertSee('forge');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(DatabaseResource::class);
            $resource->shouldReceive('get')->once()->andThrow(new Exception('Not found'));
            $mock->shouldReceive('databases')->andReturn($resource);
        });

        $response = ForgeServer::tool(GetDatabaseTool::class, ['server_id' => 1, 'database_id' => 999]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('CreateDatabaseTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(CreateDatabaseTool::class, []);

        $response->assertHasErrors();
    });

    it('validates name is required', function (): void {
        $response = ForgeServer::tool(CreateDatabaseTool::class, ['server_id' => 1]);

        $response->assertHasErrors();
    });

    it('validates password minimum length', function (): void {
        $response = ForgeServer::tool(CreateDatabaseTool::class, [
            'server_id' => 1, 'name' => 'mydb', 'password' => 'short',
        ]);

        $response->assertHasErrors();
    });

    it('creates database successfully', function (): void {
        $database = DatabaseData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'mydb',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($database): void {
            $resource = Mockery::mock(DatabaseResource::class);
            $resource->shouldReceive('create')->with(1, Mockery::type(CreateDatabaseData::class))->once()->andReturn($database);
            $mock->shouldReceive('databases')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateDatabaseTool::class, ['server_id' => 1, 'name' => 'mydb']);

        $response->assertOk()->assertSee('"success": true');
    });

    it('creates database with user and password', function (): void {
        $database = DatabaseData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'mydb',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($database): void {
            $resource = Mockery::mock(DatabaseResource::class);
            $resource->shouldReceive('create')->once()->andReturn($database);
            $mock->shouldReceive('databases')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateDatabaseTool::class, [
            'server_id' => 1, 'name' => 'mydb', 'user' => 'admin', 'password' => 'securepassword123',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(DatabaseResource::class);
            $resource->shouldReceive('create')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('databases')->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateDatabaseTool::class, ['server_id' => 1, 'name' => 'mydb']);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('DeleteDatabaseTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteDatabaseTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes database successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(DatabaseResource::class);
            $resource->shouldReceive('delete')->with(1, 1)->once();
            $mock->shouldReceive('databases')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteDatabaseTool::class, ['server_id' => 1, 'database_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(DatabaseResource::class);
            $resource->shouldReceive('delete')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('databases')->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteDatabaseTool::class, ['server_id' => 1, 'database_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('SyncDatabaseTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(SyncDatabaseTool::class, []);

        $response->assertHasErrors();
    });

    it('syncs databases successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(DatabaseResource::class);
            $resource->shouldReceive('sync')->with(1)->once();
            $mock->shouldReceive('databases')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(SyncDatabaseTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(DatabaseResource::class);
            $resource->shouldReceive('sync')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('databases')->andReturn($resource);
        });

        $response = ForgeServer::tool(SyncDatabaseTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('ListDatabaseUsersTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListDatabaseUsersTool::class, []);

        $response->assertHasErrors();
    });

    it('lists database users successfully', function (): void {
        $user = DatabaseUserData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'forge',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z', 'databases' => [1],
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($user): void {
            $resource = Mockery::mock(DatabaseUserResource::class);
            $resource->shouldReceive('list')->with(1)->once()->andReturn(new DatabaseUserCollectionData(users: [$user]));
            $mock->shouldReceive('databaseUsers')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListDatabaseUsersTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('forge');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(DatabaseUserResource::class);
            $resource->shouldReceive('list')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('databaseUsers')->andReturn($resource);
        });

        $response = ForgeServer::tool(ListDatabaseUsersTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('GetDatabaseUserTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetDatabaseUserTool::class, []);

        $response->assertHasErrors();
    });

    it('gets database user details successfully', function (): void {
        $user = DatabaseUserData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'forge',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z', 'databases' => [1],
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($user): void {
            $resource = Mockery::mock(DatabaseUserResource::class);
            $resource->shouldReceive('get')->with(1, 1)->once()->andReturn($user);
            $mock->shouldReceive('databaseUsers')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetDatabaseUserTool::class, ['server_id' => 1, 'user_id' => 1]);

        $response->assertOk()->assertSee('forge');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(DatabaseUserResource::class);
            $resource->shouldReceive('get')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('databaseUsers')->andReturn($resource);
        });

        $response = ForgeServer::tool(GetDatabaseUserTool::class, ['server_id' => 1, 'user_id' => 999]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('CreateDatabaseUserTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(CreateDatabaseUserTool::class, []);

        $response->assertHasErrors();
    });

    it('validates password is required', function (): void {
        $response = ForgeServer::tool(CreateDatabaseUserTool::class, [
            'server_id' => 1, 'name' => 'newuser',
        ]);

        $response->assertHasErrors();
    });

    it('creates database user successfully', function (): void {
        $user = DatabaseUserData::from([
            'id' => 2, 'server_id' => 1, 'name' => 'newuser',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z', 'databases' => [1],
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($user): void {
            $resource = Mockery::mock(DatabaseUserResource::class);
            $resource->shouldReceive('create')->with(1, Mockery::type(CreateDatabaseUserData::class))->once()->andReturn($user);
            $mock->shouldReceive('databaseUsers')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateDatabaseUserTool::class, [
            'server_id' => 1, 'name' => 'newuser', 'password' => 'securepassword123', 'databases' => [1],
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('creates database user without databases', function (): void {
        $user = DatabaseUserData::from([
            'id' => 2, 'server_id' => 1, 'name' => 'newuser',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z', 'databases' => [],
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($user): void {
            $resource = Mockery::mock(DatabaseUserResource::class);
            $resource->shouldReceive('create')->once()->andReturn($user);
            $mock->shouldReceive('databaseUsers')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateDatabaseUserTool::class, [
            'server_id' => 1, 'name' => 'newuser', 'password' => 'securepassword123',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(DatabaseUserResource::class);
            $resource->shouldReceive('create')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('databaseUsers')->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateDatabaseUserTool::class, [
            'server_id' => 1, 'name' => 'newuser', 'password' => 'securepassword123',
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('UpdateDatabaseUserTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(UpdateDatabaseUserTool::class, []);

        $response->assertHasErrors();
    });

    it('validates databases is required', function (): void {
        $response = ForgeServer::tool(UpdateDatabaseUserTool::class, [
            'server_id' => 1, 'user_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('updates database user successfully', function (): void {
        $user = DatabaseUserData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'forge',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z', 'databases' => [1, 2],
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($user): void {
            $resource = Mockery::mock(DatabaseUserResource::class);
            $resource->shouldReceive('update')->with(1, 1, Mockery::type(UpdateDatabaseUserData::class))->once()->andReturn($user);
            $mock->shouldReceive('databaseUsers')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(UpdateDatabaseUserTool::class, [
            'server_id' => 1, 'user_id' => 1, 'databases' => [1, 2],
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(DatabaseUserResource::class);
            $resource->shouldReceive('update')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('databaseUsers')->andReturn($resource);
        });

        $response = ForgeServer::tool(UpdateDatabaseUserTool::class, [
            'server_id' => 1, 'user_id' => 1, 'databases' => [1],
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('DeleteDatabaseUserTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteDatabaseUserTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes database user successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(DatabaseUserResource::class);
            $resource->shouldReceive('delete')->with(1, 1)->once();
            $mock->shouldReceive('databaseUsers')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteDatabaseUserTool::class, ['server_id' => 1, 'user_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(DatabaseUserResource::class);
            $resource->shouldReceive('delete')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('databaseUsers')->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteDatabaseUserTool::class, ['server_id' => 1, 'user_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

// ============================================================
// Deployments
// ============================================================

describe('DeploySiteTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeploySiteTool::class, []);

        $response->assertHasErrors();
    });

    it('deploys site successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('deploy')->with(1, 1)->once();
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeploySiteTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('deploy')->once()->andThrow(new Exception('Deployment failed'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(DeploySiteTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('EnableQuickDeployTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(EnableQuickDeployTool::class, []);

        $response->assertHasErrors();
    });

    it('enables quick deploy successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('enableQuickDeploy')->with(1, 1)->once();
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(EnableQuickDeployTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('enableQuickDeploy')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(EnableQuickDeployTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('DisableQuickDeployTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DisableQuickDeployTool::class, []);

        $response->assertHasErrors();
    });

    it('disables quick deploy successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('disableQuickDeploy')->with(1, 1)->once();
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DisableQuickDeployTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('disableQuickDeploy')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(DisableQuickDeployTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('GetDeploymentScriptTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetDeploymentScriptTool::class, []);

        $response->assertHasErrors();
    });

    it('gets deployment script successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('deploymentScript')->with(1, 1)->once()->andReturn('cd /home/forge/site && git pull');
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetDeploymentScriptTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('git pull');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('deploymentScript')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(GetDeploymentScriptTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('UpdateDeploymentScriptTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(UpdateDeploymentScriptTool::class, []);

        $response->assertHasErrors();
    });

    it('validates content is required', function (): void {
        $response = ForgeServer::tool(UpdateDeploymentScriptTool::class, [
            'server_id' => 1, 'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('updates deployment script successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('updateDeploymentScript')->with(1, 1, Mockery::type('string'))->once();
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(UpdateDeploymentScriptTool::class, [
            'server_id' => 1, 'site_id' => 1, 'content' => 'cd /home/forge/site && git pull && composer install',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('updateDeploymentScript')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(UpdateDeploymentScriptTool::class, [
            'server_id' => 1, 'site_id' => 1, 'content' => 'script content',
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('GetDeploymentLogTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetDeploymentLogTool::class, []);

        $response->assertHasErrors();
    });

    it('gets deployment log successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('deploymentLog')->with(1, 1)->once()->andReturn('Deployment completed successfully');
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetDeploymentLogTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('Deployment completed successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('deploymentLog')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(GetDeploymentLogTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('ListDeploymentHistoryTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListDeploymentHistoryTool::class, []);

        $response->assertHasErrors();
    });

    it('lists deployment history successfully', function (): void {
        $mockHistory = [
            ['id' => 1, 'commit_hash' => 'abc123', 'status' => 'finished'],
        ];

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockHistory): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('deploymentHistory')->with(1, 1)->once()->andReturn($mockHistory);
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListDeploymentHistoryTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('abc123');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('deploymentHistory')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(ListDeploymentHistoryTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('GetDeploymentHistoryDeploymentTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetDeploymentHistoryDeploymentTool::class, []);

        $response->assertHasErrors();
    });

    it('gets specific deployment successfully', function (): void {
        $mockDeployment = ['id' => 1, 'commit_hash' => 'abc123', 'status' => 'finished'];

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockDeployment): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('deploymentHistoryDeployment')->with(1, 1, 1)->once()->andReturn($mockDeployment);
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetDeploymentHistoryDeploymentTool::class, [
            'server_id' => 1, 'site_id' => 1, 'deployment_id' => 1,
        ]);

        $response->assertOk()->assertSee('abc123');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('deploymentHistoryDeployment')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(GetDeploymentHistoryDeploymentTool::class, [
            'server_id' => 1, 'site_id' => 1, 'deployment_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('GetDeploymentHistoryOutputTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetDeploymentHistoryOutputTool::class, []);

        $response->assertHasErrors();
    });

    it('gets deployment output successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('deploymentHistoryOutput')->with(1, 1, 1)->once()->andReturn(['output' => 'Deployment output']);
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetDeploymentHistoryOutputTool::class, [
            'server_id' => 1, 'site_id' => 1, 'deployment_id' => 1,
        ]);

        $response->assertOk()->assertSee('Deployment output');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('deploymentHistoryOutput')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(GetDeploymentHistoryOutputTool::class, [
            'server_id' => 1, 'site_id' => 1, 'deployment_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('ResetDeploymentStateTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ResetDeploymentStateTool::class, []);

        $response->assertHasErrors();
    });

    it('resets deployment state successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('resetDeploymentState')->with(1, 1)->once();
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ResetDeploymentStateTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('resetDeploymentState')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(ResetDeploymentStateTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('SetDeploymentFailureEmailsTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(SetDeploymentFailureEmailsTool::class, []);

        $response->assertHasErrors();
    });

    it('validates emails format', function (): void {
        $response = ForgeServer::tool(SetDeploymentFailureEmailsTool::class, [
            'server_id' => 1, 'site_id' => 1, 'emails' => ['not-an-email'],
        ]);

        $response->assertHasErrors();
    });

    it('sets deployment failure emails successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('setDeploymentFailureEmails')->with(1, 1, ['admin@example.com'])->once();
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(SetDeploymentFailureEmailsTool::class, [
            'server_id' => 1, 'site_id' => 1, 'emails' => ['admin@example.com'],
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('setDeploymentFailureEmails')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(SetDeploymentFailureEmailsTool::class, [
            'server_id' => 1, 'site_id' => 1, 'emails' => ['admin@example.com'],
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

// ============================================================
// Firewall
// ============================================================

describe('ListFirewallRulesTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListFirewallRulesTool::class, []);

        $response->assertHasErrors();
    });

    it('lists firewall rules successfully', function (): void {
        $rule = FirewallRuleData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'Allow SSH', 'port' => 22,
            'ip_address' => '0.0.0.0/0', 'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($rule): void {
            $resource = Mockery::mock(FirewallResource::class);
            $resource->shouldReceive('list')->with(1)->once()->andReturn(new FirewallRuleCollectionData(rules: [$rule]));
            $mock->shouldReceive('firewall')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListFirewallRulesTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('Allow SSH');
    });

    it('returns empty list when no rules exist', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(FirewallResource::class);
            $resource->shouldReceive('list')->once()->andReturn(new FirewallRuleCollectionData(rules: []));
            $mock->shouldReceive('firewall')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListFirewallRulesTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"count": 0');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(FirewallResource::class);
            $resource->shouldReceive('list')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('firewall')->andReturn($resource);
        });

        $response = ForgeServer::tool(ListFirewallRulesTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('GetFirewallRuleTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetFirewallRuleTool::class, []);

        $response->assertHasErrors();
    });

    it('gets firewall rule details successfully', function (): void {
        $rule = FirewallRuleData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'Allow HTTPS', 'port' => 443,
            'ip_address' => '0.0.0.0/0', 'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($rule): void {
            $resource = Mockery::mock(FirewallResource::class);
            $resource->shouldReceive('get')->with(1, 1)->once()->andReturn($rule);
            $mock->shouldReceive('firewall')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetFirewallRuleTool::class, ['server_id' => 1, 'rule_id' => 1]);

        $response->assertOk()->assertSee('Allow HTTPS');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(FirewallResource::class);
            $resource->shouldReceive('get')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('firewall')->andReturn($resource);
        });

        $response = ForgeServer::tool(GetFirewallRuleTool::class, ['server_id' => 1, 'rule_id' => 999]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('CreateFirewallRuleTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(CreateFirewallRuleTool::class, []);

        $response->assertHasErrors();
    });

    it('validates port is required', function (): void {
        $response = ForgeServer::tool(CreateFirewallRuleTool::class, [
            'server_id' => 1, 'name' => 'Allow HTTP',
        ]);

        $response->assertHasErrors();
    });

    it('creates firewall rule successfully', function (): void {
        $rule = FirewallRuleData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'Allow HTTP', 'port' => 80,
            'ip_address' => '0.0.0.0/0', 'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($rule): void {
            $resource = Mockery::mock(FirewallResource::class);
            $resource->shouldReceive('create')->with(1, Mockery::type(CreateFirewallRuleData::class))->once()->andReturn($rule);
            $mock->shouldReceive('firewall')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateFirewallRuleTool::class, [
            'server_id' => 1, 'name' => 'Allow HTTP', 'port' => '80',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('creates firewall rule with specific IP', function (): void {
        $rule = FirewallRuleData::from([
            'id' => 2, 'server_id' => 1, 'name' => 'Allow MySQL', 'port' => 3306,
            'ip_address' => '192.168.1.100', 'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($rule): void {
            $resource = Mockery::mock(FirewallResource::class);
            $resource->shouldReceive('create')->once()->andReturn($rule);
            $mock->shouldReceive('firewall')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateFirewallRuleTool::class, [
            'server_id' => 1, 'name' => 'Allow MySQL', 'port' => '3306', 'ip_address' => '192.168.1.100',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(FirewallResource::class);
            $resource->shouldReceive('create')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('firewall')->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateFirewallRuleTool::class, [
            'server_id' => 1, 'name' => 'Allow HTTP', 'port' => '80',
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('DeleteFirewallRuleTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteFirewallRuleTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes firewall rule successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(FirewallResource::class);
            $resource->shouldReceive('delete')->with(1, 1)->once();
            $mock->shouldReceive('firewall')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteFirewallRuleTool::class, ['server_id' => 1, 'rule_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(FirewallResource::class);
            $resource->shouldReceive('delete')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('firewall')->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteFirewallRuleTool::class, ['server_id' => 1, 'rule_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

// ============================================================
// Git
// ============================================================

describe('InstallGitRepositoryTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(InstallGitRepositoryTool::class, []);

        $response->assertHasErrors();
    });

    it('validates provider and repository are required', function (): void {
        $response = ForgeServer::tool(InstallGitRepositoryTool::class, [
            'server_id' => 1, 'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('installs git repository successfully', function (): void {
        $site = SiteData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'example.com', 'aliases' => null,
            'directory' => '/public', 'wildcards' => false, 'status' => 'installed',
            'repository' => 'user/repo', 'repository_provider' => 'github',
            'repository_branch' => 'main', 'repository_status' => 'installed',
            'quick_deploy' => false, 'deployment_status' => null, 'project_type' => 'php',
            'app' => null, 'app_status' => null, 'hipchat_room' => null, 'slack_channel' => null,
            'telegram_chat_id' => null, 'telegram_chat_title' => null, 'teams_webhook_url' => null,
            'discord_webhook_url' => null, 'username' => 'forge', 'balancing_status' => null,
            'created_at' => '2024-01-01T00:00:00Z', 'deployment_url' => null, 'is_secured' => false,
            'php_version' => 'php83', 'tags' => null, 'failure_deployment_emails' => null,
            'telegram_secret' => null, 'web_directory' => '/public',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($site): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('installGitRepository')
                ->with(1, 1, Mockery::type(InstallGitRepositoryData::class))
                ->once()
                ->andReturn($site);
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(InstallGitRepositoryTool::class, [
            'server_id' => 1, 'site_id' => 1, 'provider' => 'github',
            'repository' => 'user/repo', 'branch' => 'main', 'composer' => true,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('installGitRepository')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(InstallGitRepositoryTool::class, [
            'server_id' => 1, 'site_id' => 1, 'provider' => 'github', 'repository' => 'user/repo',
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('UpdateGitRepositoryTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(UpdateGitRepositoryTool::class, []);

        $response->assertHasErrors();
    });

    it('updates git repository successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('updateGitRepository')
                ->with(1, 1, Mockery::type(UpdateGitRepositoryData::class))
                ->once();
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(UpdateGitRepositoryTool::class, [
            'server_id' => 1, 'site_id' => 1, 'branch' => 'develop',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('updateGitRepository')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(UpdateGitRepositoryTool::class, [
            'server_id' => 1, 'site_id' => 1, 'branch' => 'develop',
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('DestroyGitRepositoryTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DestroyGitRepositoryTool::class, []);

        $response->assertHasErrors();
    });

    it('destroys git repository successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('destroyGitRepository')->with(1, 1)->once();
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DestroyGitRepositoryTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('destroyGitRepository')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(DestroyGitRepositoryTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('CreateDeployKeyTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(CreateDeployKeyTool::class, []);

        $response->assertHasErrors();
    });

    it('creates deploy key successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('createDeployKey')->with(1, 1)->once()->andReturn(['key' => 'ssh-rsa AAAAB3...']);
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateDeployKeyTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('createDeployKey')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateDeployKeyTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('DestroyDeployKeyTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DestroyDeployKeyTool::class, []);

        $response->assertHasErrors();
    });

    it('destroys deploy key successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('deleteDeployKey')->with(1, 1)->once();
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DestroyDeployKeyTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('deleteDeployKey')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sites')->andReturn($resource);
        });

        $response = ForgeServer::tool(DestroyDeployKeyTool::class, ['server_id' => 1, 'site_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

// ============================================================
// Jobs
// ============================================================

describe('ListScheduledJobsTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListScheduledJobsTool::class, []);

        $response->assertHasErrors();
    });

    it('lists scheduled jobs successfully', function (): void {
        $job = JobData::from([
            'id' => 1, 'server_id' => 1, 'command' => 'php artisan schedule:run',
            'user' => 'forge', 'frequency' => 'minutely', 'cron' => '* * * * *',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($job): void {
            $resource = Mockery::mock(JobResource::class);
            $resource->shouldReceive('list')->with(1)->once()->andReturn(new JobCollectionData(jobs: [$job]));
            $mock->shouldReceive('jobs')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListScheduledJobsTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('schedule:run');
    });

    it('returns empty list when no jobs exist', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(JobResource::class);
            $resource->shouldReceive('list')->once()->andReturn(new JobCollectionData(jobs: []));
            $mock->shouldReceive('jobs')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListScheduledJobsTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"count": 0');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(JobResource::class);
            $resource->shouldReceive('list')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('jobs')->andReturn($resource);
        });

        $response = ForgeServer::tool(ListScheduledJobsTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('GetScheduledJobTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetScheduledJobTool::class, []);

        $response->assertHasErrors();
    });

    it('gets scheduled job details successfully', function (): void {
        $job = JobData::from([
            'id' => 1, 'server_id' => 1, 'command' => 'php artisan schedule:run',
            'user' => 'forge', 'frequency' => 'minutely', 'cron' => '* * * * *',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($job): void {
            $resource = Mockery::mock(JobResource::class);
            $resource->shouldReceive('get')->with(1, 1)->once()->andReturn($job);
            $mock->shouldReceive('jobs')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetScheduledJobTool::class, ['server_id' => 1, 'job_id' => 1]);

        $response->assertOk()->assertSee('schedule:run');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(JobResource::class);
            $resource->shouldReceive('get')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('jobs')->andReturn($resource);
        });

        $response = ForgeServer::tool(GetScheduledJobTool::class, ['server_id' => 1, 'job_id' => 999]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('CreateScheduledJobTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(CreateScheduledJobTool::class, []);

        $response->assertHasErrors();
    });

    it('validates frequency is required', function (): void {
        $response = ForgeServer::tool(CreateScheduledJobTool::class, [
            'server_id' => 1, 'command' => 'php artisan schedule:run',
        ]);

        $response->assertHasErrors();
    });

    it('validates frequency must be valid value', function (): void {
        $response = ForgeServer::tool(CreateScheduledJobTool::class, [
            'server_id' => 1, 'command' => 'php artisan schedule:run', 'frequency' => 'invalid',
        ]);

        $response->assertHasErrors();
    });

    it('creates scheduled job with minutely frequency', function (): void {
        $job = JobData::from([
            'id' => 1, 'server_id' => 1, 'command' => 'php artisan schedule:run',
            'user' => 'forge', 'frequency' => 'minutely', 'cron' => '* * * * *',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($job): void {
            $resource = Mockery::mock(JobResource::class);
            $resource->shouldReceive('create')->with(1, Mockery::type(CreateJobData::class))->once()->andReturn($job);
            $mock->shouldReceive('jobs')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateScheduledJobTool::class, [
            'server_id' => 1, 'command' => 'php artisan schedule:run', 'frequency' => 'minutely',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('creates scheduled job with custom frequency', function (): void {
        $job = JobData::from([
            'id' => 2, 'server_id' => 1, 'command' => 'php artisan backup:run',
            'user' => 'forge', 'frequency' => 'custom', 'cron' => '0 2 * * 0',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($job): void {
            $resource = Mockery::mock(JobResource::class);
            $resource->shouldReceive('create')->once()->andReturn($job);
            $mock->shouldReceive('jobs')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateScheduledJobTool::class, [
            'server_id' => 1, 'command' => 'php artisan backup:run', 'frequency' => 'custom',
            'minute' => '0', 'hour' => '2', 'day' => '*', 'month' => '*', 'weekday' => '0',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('creates scheduled job with custom user', function (): void {
        $job = JobData::from([
            'id' => 3, 'server_id' => 1, 'command' => 'php artisan queue:work',
            'user' => 'root', 'frequency' => 'hourly', 'cron' => '0 * * * *',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($job): void {
            $resource = Mockery::mock(JobResource::class);
            $resource->shouldReceive('create')->once()->andReturn($job);
            $mock->shouldReceive('jobs')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateScheduledJobTool::class, [
            'server_id' => 1, 'command' => 'php artisan queue:work', 'frequency' => 'hourly', 'user' => 'root',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(JobResource::class);
            $resource->shouldReceive('create')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('jobs')->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateScheduledJobTool::class, [
            'server_id' => 1, 'command' => 'php artisan schedule:run', 'frequency' => 'minutely',
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('DeleteScheduledJobTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteScheduledJobTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes scheduled job successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(JobResource::class);
            $resource->shouldReceive('delete')->with(1, 1)->once();
            $mock->shouldReceive('jobs')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteScheduledJobTool::class, ['server_id' => 1, 'job_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(JobResource::class);
            $resource->shouldReceive('delete')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('jobs')->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteScheduledJobTool::class, ['server_id' => 1, 'job_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('GetJobOutputTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetJobOutputTool::class, []);

        $response->assertHasErrors();
    });

    it('gets job output successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(JobResource::class);
            $resource->shouldReceive('getOutput')->with(1, 1)->once()->andReturn('Job completed successfully');
            $mock->shouldReceive('jobs')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetJobOutputTool::class, ['server_id' => 1, 'job_id' => 1]);

        $response->assertOk()->assertSee('Job completed successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(JobResource::class);
            $resource->shouldReceive('getOutput')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('jobs')->andReturn($resource);
        });

        $response = ForgeServer::tool(GetJobOutputTool::class, ['server_id' => 1, 'job_id' => 1]);

        $response->assertOk()->assertSee('"success": false');
    });
});
