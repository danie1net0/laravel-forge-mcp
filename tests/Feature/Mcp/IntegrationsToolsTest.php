<?php

declare(strict_types=1);

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Resources\IntegrationResource;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Integrations\{DisableHorizonTool, DisableInertiaTool, DisableMaintenanceTool, DisableOctaneTool, DisablePulseTool, DisableReverbTool, DisableSchedulerTool, EnableHorizonTool, EnableInertiaTool, EnableMaintenanceTool, EnableOctaneTool, EnablePulseTool, EnableReverbTool, EnableSchedulerTool, GetHorizonTool, GetInertiaTool, GetMaintenanceTool, GetOctaneTool, GetPulseTool, GetReverbTool};

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

describe('GetHorizonTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetHorizonTool::class, []);

        $response->assertHasErrors();
    });

    it('gets horizon status successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('getHorizon')->with(1, 1)->once()->andReturn([
                'enabled' => true,
                'status' => 'running',
            ]);
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(GetHorizonTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('running');
    });
});

describe('EnableHorizonTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(EnableHorizonTool::class, []);

        $response->assertHasErrors();
    });

    it('enables horizon successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enableHorizon')->with(1, 1)->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnableHorizonTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('DisableHorizonTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(DisableHorizonTool::class, []);

        $response->assertHasErrors();
    });

    it('disables horizon successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('disableHorizon')->with(1, 1)->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(DisableHorizonTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('GetOctaneTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetOctaneTool::class, []);

        $response->assertHasErrors();
    });

    it('gets octane status successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('getOctane')->with(1, 1)->once()->andReturn([
                'enabled' => true,
                'server' => 'swoole',
            ]);
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(GetOctaneTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('swoole');
    });
});

describe('EnableOctaneTool', function (): void {
    it('requires mandatory parameters', function (): void {
        $response = ForgeServer::tool(EnableOctaneTool::class, []);

        $response->assertHasErrors();
    });

    it('enables octane successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enableOctane')->with(1, 1, 'swoole', 'auto')->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnableOctaneTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'server' => 'swoole',
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('DisableOctaneTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(DisableOctaneTool::class, []);

        $response->assertHasErrors();
    });

    it('disables octane successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('disableOctane')->with(1, 1)->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(DisableOctaneTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('GetReverbTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetReverbTool::class, []);

        $response->assertHasErrors();
    });

    it('gets reverb status successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('getReverb')->with(1, 1)->once()->andReturn([
                'enabled' => true,
            ]);
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(GetReverbTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk();
    });
});

describe('EnableReverbTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(EnableReverbTool::class, []);

        $response->assertHasErrors();
    });

    it('enables reverb successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enableReverb')->with(1, 1)->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnableReverbTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('DisableReverbTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(DisableReverbTool::class, []);

        $response->assertHasErrors();
    });

    it('disables reverb successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('disableReverb')->with(1, 1)->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(DisableReverbTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('GetPulseTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetPulseTool::class, []);

        $response->assertHasErrors();
    });

    it('gets pulse status successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('getPulse')->with(1, 1)->once()->andReturn([
                'enabled' => true,
            ]);
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(GetPulseTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk();
    });
});

describe('EnablePulseTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(EnablePulseTool::class, []);

        $response->assertHasErrors();
    });

    it('enables pulse successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enablePulse')->with(1, 1)->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnablePulseTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('DisablePulseTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(DisablePulseTool::class, []);

        $response->assertHasErrors();
    });

    it('disables pulse successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('disablePulse')->with(1, 1)->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(DisablePulseTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('GetInertiaTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetInertiaTool::class, []);

        $response->assertHasErrors();
    });

    it('gets inertia status successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('getInertia')->with(1, 1)->once()->andReturn([
                'enabled' => true,
            ]);
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(GetInertiaTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk();
    });
});

describe('EnableInertiaTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(EnableInertiaTool::class, []);

        $response->assertHasErrors();
    });

    it('enables inertia successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enableInertia')->with(1, 1)->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnableInertiaTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('DisableInertiaTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(DisableInertiaTool::class, []);

        $response->assertHasErrors();
    });

    it('disables inertia successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('disableInertia')->with(1, 1)->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(DisableInertiaTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('GetMaintenanceTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetMaintenanceTool::class, []);

        $response->assertHasErrors();
    });

    it('gets maintenance status successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('getMaintenance')->with(1, 1)->once()->andReturn([
                'enabled' => false,
            ]);
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(GetMaintenanceTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk();
    });
});

describe('EnableMaintenanceTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(EnableMaintenanceTool::class, []);

        $response->assertHasErrors();
    });

    it('enables maintenance mode successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enableMaintenance')->with(1, 1, null, null)->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnableMaintenanceTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('DisableMaintenanceTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(DisableMaintenanceTool::class, []);

        $response->assertHasErrors();
    });

    it('disables maintenance mode successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('disableMaintenance')->with(1, 1)->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(DisableMaintenanceTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('EnableSchedulerTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(EnableSchedulerTool::class, []);

        $response->assertHasErrors();
    });

    it('enables scheduler successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('enableScheduler')->with(1, 1)->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(EnableSchedulerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('DisableSchedulerTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(DisableSchedulerTool::class, []);

        $response->assertHasErrors();
    });

    it('disables scheduler successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $integrationResource = Mockery::mock(IntegrationResource::class);
            $integrationResource->shouldReceive('disableScheduler')->with(1, 1)->once();
            $mock->shouldReceive('integrations')->once()->andReturn($integrationResource);
        });

        $response = ForgeServer::tool(DisableSchedulerTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('Integration Tools Structure', function (): void {
    it('all integration tools can be instantiated', function (): void {
        $tools = [
            GetHorizonTool::class,
            EnableHorizonTool::class,
            DisableHorizonTool::class,
            GetOctaneTool::class,
            EnableOctaneTool::class,
            DisableOctaneTool::class,
            GetReverbTool::class,
            EnableReverbTool::class,
            DisableReverbTool::class,
            GetPulseTool::class,
            EnablePulseTool::class,
            DisablePulseTool::class,
            GetInertiaTool::class,
            EnableInertiaTool::class,
            DisableInertiaTool::class,
            GetMaintenanceTool::class,
            EnableMaintenanceTool::class,
            DisableMaintenanceTool::class,
            EnableSchedulerTool::class,
            DisableSchedulerTool::class,
        ];

        foreach ($tools as $toolClass) {
            $tool = app($toolClass);
            expect($tool->name())->toBeString()->not->toBeEmpty();
            expect($tool->description())->toBeString()->not->toBeEmpty();
        }
    });
});
