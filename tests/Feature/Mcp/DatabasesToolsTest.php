<?php

declare(strict_types=1);

use App\Integrations\Forge\ForgeClient;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Databases\{CreateDatabaseTool, CreateDatabaseUserTool, DeleteDatabaseTool, DeleteDatabaseUserTool, GetDatabaseTool, GetDatabaseUserTool, ListDatabaseUsersTool, ListDatabasesTool, SyncDatabaseTool, UpdateDatabaseUserTool};
use App\Integrations\Forge\Resources\{DatabaseResource, DatabaseUserResource};
use App\Integrations\Forge\Data\Databases\{CreateDatabaseData, CreateDatabaseUserData, DatabaseCollectionData, DatabaseData, DatabaseUserCollectionData, DatabaseUserData, UpdateDatabaseUserData};

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

function createMockDatabase(int $id = 1, string $name = 'forge'): DatabaseData
{
    return DatabaseData::from([
        'id' => $id,
        'server_id' => 1,
        'name' => $name,
        'status' => 'installed',
        'created_at' => '2024-01-01T00:00:00Z',
    ]);
}

function createMockDatabaseUser(int $id = 1, string $name = 'forge'): DatabaseUserData
{
    return DatabaseUserData::from([
        'id' => $id,
        'server_id' => 1,
        'name' => $name,
        'status' => 'installed',
        'created_at' => '2024-01-01T00:00:00Z',
        'databases' => ['forge'],
    ]);
}

describe('ListDatabasesTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListDatabasesTool::class, []);

        $response->assertHasErrors();
    });

    it('lists databases successfully', function (): void {
        $mockDatabase = createMockDatabase();

        $this->mock(ForgeClient::class, function ($mock) use ($mockDatabase): void {
            $dbResource = Mockery::mock(DatabaseResource::class);
            $collection = new DatabaseCollectionData(databases: [$mockDatabase]);
            $dbResource->shouldReceive('list')->with(1)->once()->andReturn($collection);
            $mock->shouldReceive('databases')->once()->andReturn($dbResource);
        });

        $response = ForgeServer::tool(ListDatabasesTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('forge');
    });

    it('returns empty list when no databases exist', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $dbResource = Mockery::mock(DatabaseResource::class);
            $collection = new DatabaseCollectionData(databases: []);
            $dbResource->shouldReceive('list')->once()->andReturn($collection);
            $mock->shouldReceive('databases')->once()->andReturn($dbResource);
        });

        $response = ForgeServer::tool(ListDatabasesTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"count": 0');
    });
});

describe('GetDatabaseTool', function (): void {
    it('requires server_id and database_id parameters', function (): void {
        $response = ForgeServer::tool(GetDatabaseTool::class, []);

        $response->assertHasErrors();
    });

    it('gets database details successfully', function (): void {
        $mockDatabase = createMockDatabase();

        $this->mock(ForgeClient::class, function ($mock) use ($mockDatabase): void {
            $dbResource = Mockery::mock(DatabaseResource::class);
            $dbResource->shouldReceive('get')->with(1, 1)->once()->andReturn($mockDatabase);
            $mock->shouldReceive('databases')->once()->andReturn($dbResource);
        });

        $response = ForgeServer::tool(GetDatabaseTool::class, [
            'server_id' => 1,
            'database_id' => 1,
        ]);

        $response->assertOk()->assertSee('forge');
    });
});

describe('CreateDatabaseTool', function (): void {
    it('requires server_id and name parameters', function (): void {
        $response = ForgeServer::tool(CreateDatabaseTool::class, []);

        $response->assertHasErrors();
    });

    it('requires name parameter', function (): void {
        $response = ForgeServer::tool(CreateDatabaseTool::class, ['server_id' => 1]);

        $response->assertHasErrors();
    });

    it('validates password minimum length', function (): void {
        $response = ForgeServer::tool(CreateDatabaseTool::class, [
            'server_id' => 1,
            'name' => 'mydb',
            'password' => 'short',
        ]);

        $response->assertHasErrors();
    });

    it('creates database successfully', function (): void {
        $mockDatabase = createMockDatabase(1, 'mydb');

        $this->mock(ForgeClient::class, function ($mock) use ($mockDatabase): void {
            $dbResource = Mockery::mock(DatabaseResource::class);
            $dbResource->shouldReceive('create')
                ->with(1, Mockery::type(CreateDatabaseData::class))
                ->once()
                ->andReturn($mockDatabase);
            $mock->shouldReceive('databases')->once()->andReturn($dbResource);
        });

        $response = ForgeServer::tool(CreateDatabaseTool::class, [
            'server_id' => 1,
            'name' => 'mydb',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('creates database with user and password', function (): void {
        $mockDatabase = createMockDatabase(1, 'mydb');

        $this->mock(ForgeClient::class, function ($mock) use ($mockDatabase): void {
            $dbResource = Mockery::mock(DatabaseResource::class);
            $dbResource->shouldReceive('create')->once()->andReturn($mockDatabase);
            $mock->shouldReceive('databases')->once()->andReturn($dbResource);
        });

        $response = ForgeServer::tool(CreateDatabaseTool::class, [
            'server_id' => 1,
            'name' => 'mydb',
            'user' => 'myuser',
            'password' => 'securepassword123',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('DeleteDatabaseTool', function (): void {
    it('requires server_id and database_id parameters', function (): void {
        $response = ForgeServer::tool(DeleteDatabaseTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes database successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $dbResource = Mockery::mock(DatabaseResource::class);
            $dbResource->shouldReceive('delete')->with(1, 1)->once();
            $mock->shouldReceive('databases')->once()->andReturn($dbResource);
        });

        $response = ForgeServer::tool(DeleteDatabaseTool::class, [
            'server_id' => 1,
            'database_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('ListDatabaseUsersTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListDatabaseUsersTool::class, []);

        $response->assertHasErrors();
    });

    it('lists database users successfully', function (): void {
        $mockUser = createMockDatabaseUser();

        $this->mock(ForgeClient::class, function ($mock) use ($mockUser): void {
            $dbUserResource = Mockery::mock(DatabaseUserResource::class);
            $collection = new DatabaseUserCollectionData(users: [$mockUser]);
            $dbUserResource->shouldReceive('list')->with(1)->once()->andReturn($collection);
            $mock->shouldReceive('databaseUsers')->once()->andReturn($dbUserResource);
        });

        $response = ForgeServer::tool(ListDatabaseUsersTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('forge');
    });
});

describe('GetDatabaseUserTool', function (): void {
    it('requires server_id and user_id parameters', function (): void {
        $response = ForgeServer::tool(GetDatabaseUserTool::class, []);

        $response->assertHasErrors();
    });

    it('gets database user details successfully', function (): void {
        $mockUser = createMockDatabaseUser();

        $this->mock(ForgeClient::class, function ($mock) use ($mockUser): void {
            $dbUserResource = Mockery::mock(DatabaseUserResource::class);
            $dbUserResource->shouldReceive('get')->with(1, 1)->once()->andReturn($mockUser);
            $mock->shouldReceive('databaseUsers')->once()->andReturn($dbUserResource);
        });

        $response = ForgeServer::tool(GetDatabaseUserTool::class, [
            'server_id' => 1,
            'user_id' => 1,
        ]);

        $response->assertOk()->assertSee('forge');
    });
});

describe('CreateDatabaseUserTool', function (): void {
    it('requires mandatory parameters', function (): void {
        $response = ForgeServer::tool(CreateDatabaseUserTool::class, []);

        $response->assertHasErrors();
    });

    it('creates database user successfully', function (): void {
        $mockUser = createMockDatabaseUser(1, 'newuser');

        $this->mock(ForgeClient::class, function ($mock) use ($mockUser): void {
            $dbUserResource = Mockery::mock(DatabaseUserResource::class);
            $dbUserResource->shouldReceive('create')
                ->with(1, Mockery::type(CreateDatabaseUserData::class))
                ->once()
                ->andReturn($mockUser);
            $mock->shouldReceive('databaseUsers')->once()->andReturn($dbUserResource);
        });

        $response = ForgeServer::tool(CreateDatabaseUserTool::class, [
            'server_id' => 1,
            'name' => 'newuser',
            'password' => 'securepassword123',
            'databases' => [1],
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('UpdateDatabaseUserTool', function (): void {
    it('requires mandatory parameters', function (): void {
        $response = ForgeServer::tool(UpdateDatabaseUserTool::class, []);

        $response->assertHasErrors();
    });

    it('updates database user successfully', function (): void {
        $mockUser = createMockDatabaseUser();

        $this->mock(ForgeClient::class, function ($mock) use ($mockUser): void {
            $dbUserResource = Mockery::mock(DatabaseUserResource::class);
            $dbUserResource->shouldReceive('update')
                ->with(1, 1, Mockery::type(UpdateDatabaseUserData::class))
                ->once()
                ->andReturn($mockUser);
            $mock->shouldReceive('databaseUsers')->once()->andReturn($dbUserResource);
        });

        $response = ForgeServer::tool(UpdateDatabaseUserTool::class, [
            'server_id' => 1,
            'user_id' => 1,
            'databases' => ['forge', 'newdb'],
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('DeleteDatabaseUserTool', function (): void {
    it('requires server_id and user_id parameters', function (): void {
        $response = ForgeServer::tool(DeleteDatabaseUserTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes database user successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $dbUserResource = Mockery::mock(DatabaseUserResource::class);
            $dbUserResource->shouldReceive('delete')->with(1, 1)->once();
            $mock->shouldReceive('databaseUsers')->once()->andReturn($dbUserResource);
        });

        $response = ForgeServer::tool(DeleteDatabaseUserTool::class, [
            'server_id' => 1,
            'user_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
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
            $dbResource->shouldReceive('sync')->with(1)->once();
            $mock->shouldReceive('databases')->once()->andReturn($dbResource);
        });

        $response = ForgeServer::tool(SyncDatabaseTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('Database Tools Structure', function (): void {
    it('all database tools can be instantiated', function (): void {
        $tools = [
            ListDatabasesTool::class,
            GetDatabaseTool::class,
            CreateDatabaseTool::class,
            DeleteDatabaseTool::class,
            ListDatabaseUsersTool::class,
            GetDatabaseUserTool::class,
            CreateDatabaseUserTool::class,
            UpdateDatabaseUserTool::class,
            DeleteDatabaseUserTool::class,
            SyncDatabaseTool::class,
        ];

        foreach ($tools as $toolClass) {
            $tool = app($toolClass);
            expect($tool->name())->toBeString()->not->toBeEmpty();
            expect($tool->description())->toBeString()->not->toBeEmpty();
        }
    });
});
