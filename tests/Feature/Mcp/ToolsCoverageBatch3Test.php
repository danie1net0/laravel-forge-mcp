<?php

declare(strict_types=1);

use App\Integrations\Forge\Data\Monitors\{MonitorCollectionData, MonitorData};
use App\Integrations\Forge\Data\NginxTemplates\{NginxTemplateCollectionData, NginxTemplateData};
use App\Integrations\Forge\Data\Recipes\{RecipeCollectionData, RecipeData};
use App\Integrations\Forge\Data\RedirectRules\{RedirectRuleCollectionData, RedirectRuleData};
use App\Integrations\Forge\Data\SecurityRules\{SecurityRuleCollectionData, SecurityRuleData};
use App\Integrations\Forge\Data\SSHKeys\{SSHKeyCollectionData, SSHKeyData};
use App\Integrations\Forge\Data\User\UserData;
use App\Integrations\Forge\Data\Webhooks\{WebhookCollectionData, WebhookData};
use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Resources\{MonitorResource, NginxTemplateResource, PhpResource, RecipeResource, RedirectRuleResource, RegionResource, SecurityRuleResource, SSHKeyResource, UserResource, WebhookResource};
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Monitors\{CreateMonitorTool, DeleteMonitorTool, GetMonitorTool, ListMonitorsTool};
use App\Mcp\Tools\NginxTemplates\{CreateNginxTemplateTool, DeleteNginxTemplateTool, GetNginxDefaultTemplateTool, GetNginxTemplateTool, ListNginxTemplatesTool, UpdateNginxTemplateTool};
use App\Mcp\Tools\Php\{DisableOpcacheTool, EnableOpcacheTool, InstallPhpTool, ListPhpVersionsTool, UpdatePhpTool};
use App\Mcp\Tools\Recipes\{CreateRecipeTool, DeleteRecipeTool, GetRecipeTool, ListRecipesTool, RunRecipeTool, UpdateRecipeTool};
use App\Mcp\Tools\RedirectRules\{CreateRedirectRuleTool, DeleteRedirectRuleTool, GetRedirectRuleTool, ListRedirectRulesTool};
use App\Mcp\Tools\Regions\ListRegionsTool;
use App\Mcp\Tools\SecurityRules\{CreateSecurityRuleTool, DeleteSecurityRuleTool, GetSecurityRuleTool, ListSecurityRulesTool};
use App\Mcp\Tools\SSHKeys\{CreateSSHKeyTool, DeleteSSHKeyTool, GetSSHKeyTool, ListSSHKeysTool};
use App\Mcp\Tools\User\GetUserTool;
use App\Mcp\Tools\Webhooks\{CreateWebhookTool, DeleteWebhookTool, GetWebhookTool, ListWebhooksTool};

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

// ──────────────────────────────────────────────
// Monitors
// ──────────────────────────────────────────────

describe('ListMonitorsTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListMonitorsTool::class, []);
        $response->assertHasErrors();
    });

    it('lists monitors successfully', function (): void {
        $monitorData = MonitorData::from([
            'id' => 1,
            'server_id' => 100,
            'status' => 'active',
            'type' => 'cpu',
            'operator' => '>',
            'threshold' => 80,
            'minutes' => 5,
            'state' => 'OK',
            'state_changed_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($monitorData): void {
            $resource = Mockery::mock(MonitorResource::class);
            $resource->shouldReceive('list')
                ->with(100)
                ->once()
                ->andReturn(new MonitorCollectionData(monitors: [$monitorData]));
            $mock->shouldReceive('monitors')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListMonitorsTool::class, ['server_id' => 100]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('"count": 1');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(MonitorResource::class);
            $resource->shouldReceive('list')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('monitors')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListMonitorsTool::class, ['server_id' => 100]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('GetMonitorTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetMonitorTool::class, []);
        $response->assertHasErrors();
    });

    it('gets monitor successfully', function (): void {
        $monitorData = MonitorData::from([
            'id' => 5,
            'server_id' => 100,
            'status' => 'active',
            'type' => 'disk',
            'operator' => '>',
            'threshold' => 90,
            'minutes' => 10,
            'state' => 'OK',
            'state_changed_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($monitorData): void {
            $resource = Mockery::mock(MonitorResource::class);
            $resource->shouldReceive('get')
                ->with(100, 5)
                ->once()
                ->andReturn($monitorData);
            $mock->shouldReceive('monitors')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetMonitorTool::class, ['server_id' => 100, 'monitor_id' => 5]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('"type": "disk"');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(MonitorResource::class);
            $resource->shouldReceive('get')
                ->once()
                ->andThrow(new Exception('Not found'));
            $mock->shouldReceive('monitors')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetMonitorTool::class, ['server_id' => 100, 'monitor_id' => 999]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('CreateMonitorTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(CreateMonitorTool::class, []);
        $response->assertHasErrors();
    });

    it('creates monitor successfully', function (): void {
        $monitorData = MonitorData::from([
            'id' => 10,
            'server_id' => 100,
            'status' => 'installing',
            'type' => 'cpu',
            'operator' => '>',
            'threshold' => 80,
            'minutes' => 5,
            'state' => 'OK',
            'state_changed_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($monitorData): void {
            $resource = Mockery::mock(MonitorResource::class);
            $resource->shouldReceive('create')
                ->once()
                ->andReturn($monitorData);
            $mock->shouldReceive('monitors')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateMonitorTool::class, [
            'server_id' => 100,
            'type' => 'cpu',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Monitor created successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(MonitorResource::class);
            $resource->shouldReceive('create')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('monitors')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateMonitorTool::class, [
            'server_id' => 100,
            'type' => 'cpu',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('DeleteMonitorTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteMonitorTool::class, []);
        $response->assertHasErrors();
    });

    it('deletes monitor successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(MonitorResource::class);
            $resource->shouldReceive('delete')
                ->with(100, 5)
                ->once();
            $mock->shouldReceive('monitors')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteMonitorTool::class, ['server_id' => 100, 'monitor_id' => 5]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('deleted successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(MonitorResource::class);
            $resource->shouldReceive('delete')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('monitors')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteMonitorTool::class, ['server_id' => 100, 'monitor_id' => 5]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

// ──────────────────────────────────────────────
// NginxTemplates
// ──────────────────────────────────────────────

describe('ListNginxTemplatesTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListNginxTemplatesTool::class, []);
        $response->assertHasErrors();
    });

    it('lists templates successfully', function (): void {
        $templateData = NginxTemplateData::from([
            'id' => 1,
            'server_id' => 100,
            'name' => 'default-template',
            'content' => 'server { }',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($templateData): void {
            $resource = Mockery::mock(NginxTemplateResource::class);
            $resource->shouldReceive('list')
                ->with(100)
                ->once()
                ->andReturn(new NginxTemplateCollectionData(templates: [$templateData]));
            $mock->shouldReceive('nginxTemplates')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListNginxTemplatesTool::class, ['server_id' => 100]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('"count": 1');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(NginxTemplateResource::class);
            $resource->shouldReceive('list')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('nginxTemplates')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListNginxTemplatesTool::class, ['server_id' => 100]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('GetNginxTemplateTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetNginxTemplateTool::class, []);
        $response->assertHasErrors();
    });

    it('gets template successfully', function (): void {
        $templateData = NginxTemplateData::from([
            'id' => 5,
            'server_id' => 100,
            'name' => 'custom-template',
            'content' => 'server { listen 80; }',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($templateData): void {
            $resource = Mockery::mock(NginxTemplateResource::class);
            $resource->shouldReceive('get')
                ->with(100, 5)
                ->once()
                ->andReturn($templateData);
            $mock->shouldReceive('nginxTemplates')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetNginxTemplateTool::class, ['server_id' => 100, 'template_id' => 5]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('custom-template');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(NginxTemplateResource::class);
            $resource->shouldReceive('get')
                ->once()
                ->andThrow(new Exception('Not found'));
            $mock->shouldReceive('nginxTemplates')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetNginxTemplateTool::class, ['server_id' => 100, 'template_id' => 999]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('GetNginxDefaultTemplateTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetNginxDefaultTemplateTool::class, []);
        $response->assertHasErrors();
    });

    it('gets default template successfully', function (): void {
        $templateData = NginxTemplateData::from([
            'id' => 1,
            'server_id' => 100,
            'name' => 'default',
            'content' => 'server { listen 80; }',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($templateData): void {
            $resource = Mockery::mock(NginxTemplateResource::class);
            $resource->shouldReceive('default')
                ->with(100)
                ->once()
                ->andReturn($templateData);
            $mock->shouldReceive('nginxTemplates')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetNginxDefaultTemplateTool::class, ['server_id' => 100]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('default');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(NginxTemplateResource::class);
            $resource->shouldReceive('default')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('nginxTemplates')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetNginxDefaultTemplateTool::class, ['server_id' => 100]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('CreateNginxTemplateTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(CreateNginxTemplateTool::class, []);
        $response->assertHasErrors();
    });

    it('creates template successfully', function (): void {
        $templateData = NginxTemplateData::from([
            'id' => 10,
            'server_id' => 100,
            'name' => 'new-template',
            'content' => 'server { listen 443 ssl; }',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($templateData): void {
            $resource = Mockery::mock(NginxTemplateResource::class);
            $resource->shouldReceive('create')
                ->once()
                ->andReturn($templateData);
            $mock->shouldReceive('nginxTemplates')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateNginxTemplateTool::class, [
            'server_id' => 100,
            'name' => 'new-template',
            'content' => 'server { listen 443 ssl; }',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Nginx template created successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(NginxTemplateResource::class);
            $resource->shouldReceive('create')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('nginxTemplates')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateNginxTemplateTool::class, [
            'server_id' => 100,
            'name' => 'new-template',
            'content' => 'server { }',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('UpdateNginxTemplateTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(UpdateNginxTemplateTool::class, []);
        $response->assertHasErrors();
    });

    it('updates template successfully', function (): void {
        $templateData = NginxTemplateData::from([
            'id' => 5,
            'server_id' => 100,
            'name' => 'updated-template',
            'content' => 'server { listen 8080; }',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($templateData): void {
            $resource = Mockery::mock(NginxTemplateResource::class);
            $resource->shouldReceive('update')
                ->once()
                ->andReturn($templateData);
            $mock->shouldReceive('nginxTemplates')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(UpdateNginxTemplateTool::class, [
            'server_id' => 100,
            'template_id' => 5,
            'name' => 'updated-template',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Nginx template updated successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(NginxTemplateResource::class);
            $resource->shouldReceive('update')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('nginxTemplates')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(UpdateNginxTemplateTool::class, [
            'server_id' => 100,
            'template_id' => 5,
            'name' => 'updated-template',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('DeleteNginxTemplateTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteNginxTemplateTool::class, []);
        $response->assertHasErrors();
    });

    it('deletes template successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(NginxTemplateResource::class);
            $resource->shouldReceive('delete')
                ->with(100, 5)
                ->once();
            $mock->shouldReceive('nginxTemplates')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteNginxTemplateTool::class, ['server_id' => 100, 'template_id' => 5]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Nginx template deleted successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(NginxTemplateResource::class);
            $resource->shouldReceive('delete')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('nginxTemplates')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteNginxTemplateTool::class, ['server_id' => 100, 'template_id' => 5]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

// ──────────────────────────────────────────────
// Php
// ──────────────────────────────────────────────

describe('ListPhpVersionsTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListPhpVersionsTool::class, []);
        $response->assertHasErrors();
    });

    it('lists PHP versions successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(PhpResource::class);
            $resource->shouldReceive('list')
                ->with(100)
                ->once()
                ->andReturn([['version' => 'php83', 'status' => 'installed']]);
            $mock->shouldReceive('php')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListPhpVersionsTool::class, ['server_id' => 100]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('php83');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(PhpResource::class);
            $resource->shouldReceive('list')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('php')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListPhpVersionsTool::class, ['server_id' => 100]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('InstallPhpTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(InstallPhpTool::class, []);
        $response->assertHasErrors();
    });

    it('installs PHP version successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(PhpResource::class);
            $resource->shouldReceive('install')
                ->with(100, 'php83')
                ->once();
            $mock->shouldReceive('php')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(InstallPhpTool::class, [
            'server_id' => 100,
            'version' => 'php83',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('installation initiated');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(PhpResource::class);
            $resource->shouldReceive('install')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('php')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(InstallPhpTool::class, [
            'server_id' => 100,
            'version' => 'php83',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('UpdatePhpTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(UpdatePhpTool::class, []);
        $response->assertHasErrors();
    });

    it('updates PHP version successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(PhpResource::class);
            $resource->shouldReceive('update')
                ->with(100, 'php83')
                ->once();
            $mock->shouldReceive('php')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(UpdatePhpTool::class, [
            'server_id' => 100,
            'version' => 'php83',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('update initiated');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(PhpResource::class);
            $resource->shouldReceive('update')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('php')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(UpdatePhpTool::class, [
            'server_id' => 100,
            'version' => 'php83',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('EnableOpcacheTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(EnableOpcacheTool::class, []);
        $response->assertHasErrors();
    });

    it('enables OPcache successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(PhpResource::class);
            $resource->shouldReceive('enableOpcache')
                ->with(100)
                ->once();
            $mock->shouldReceive('php')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(EnableOpcacheTool::class, ['server_id' => 100]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('OPcache enabled successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(PhpResource::class);
            $resource->shouldReceive('enableOpcache')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('php')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(EnableOpcacheTool::class, ['server_id' => 100]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('DisableOpcacheTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DisableOpcacheTool::class, []);
        $response->assertHasErrors();
    });

    it('disables OPcache successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(PhpResource::class);
            $resource->shouldReceive('disableOpcache')
                ->with(100)
                ->once();
            $mock->shouldReceive('php')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DisableOpcacheTool::class, ['server_id' => 100]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('OPcache disabled successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(PhpResource::class);
            $resource->shouldReceive('disableOpcache')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('php')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DisableOpcacheTool::class, ['server_id' => 100]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

// ──────────────────────────────────────────────
// Recipes
// ──────────────────────────────────────────────

describe('ListRecipesTool', function (): void {
    it('lists recipes successfully', function (): void {
        $recipeData = RecipeData::from([
            'id' => 1,
            'key' => 'abc123',
            'name' => 'Deploy Script',
            'user' => 'root',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($recipeData): void {
            $resource = Mockery::mock(RecipeResource::class);
            $resource->shouldReceive('list')
                ->once()
                ->andReturn(new RecipeCollectionData(recipes: [$recipeData]));
            $mock->shouldReceive('recipes')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListRecipesTool::class, []);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('"count": 1');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RecipeResource::class);
            $resource->shouldReceive('list')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('recipes')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListRecipesTool::class, []);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('GetRecipeTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetRecipeTool::class, []);
        $response->assertHasErrors();
    });

    it('gets recipe successfully', function (): void {
        $recipeData = RecipeData::from([
            'id' => 5,
            'key' => 'xyz789',
            'name' => 'Setup Script',
            'user' => 'forge',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($recipeData): void {
            $resource = Mockery::mock(RecipeResource::class);
            $resource->shouldReceive('get')
                ->with(5)
                ->once()
                ->andReturn($recipeData);
            $mock->shouldReceive('recipes')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetRecipeTool::class, ['recipe_id' => 5]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Setup Script');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RecipeResource::class);
            $resource->shouldReceive('get')
                ->once()
                ->andThrow(new Exception('Not found'));
            $mock->shouldReceive('recipes')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetRecipeTool::class, ['recipe_id' => 999]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('CreateRecipeTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(CreateRecipeTool::class, []);
        $response->assertHasErrors();
    });

    it('creates recipe successfully', function (): void {
        $recipeData = RecipeData::from([
            'id' => 10,
            'key' => 'new123',
            'name' => 'New Recipe',
            'user' => 'root',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($recipeData): void {
            $resource = Mockery::mock(RecipeResource::class);
            $resource->shouldReceive('create')
                ->once()
                ->andReturn($recipeData);
            $mock->shouldReceive('recipes')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateRecipeTool::class, [
            'name' => 'New Recipe',
            'user' => 'root',
            'script' => 'echo "hello"',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Recipe created successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RecipeResource::class);
            $resource->shouldReceive('create')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('recipes')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateRecipeTool::class, [
            'name' => 'New Recipe',
            'user' => 'root',
            'script' => 'echo "hello"',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('UpdateRecipeTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(UpdateRecipeTool::class, []);
        $response->assertHasErrors();
    });

    it('updates recipe successfully', function (): void {
        $recipeData = RecipeData::from([
            'id' => 5,
            'key' => 'upd123',
            'name' => 'Updated Recipe',
            'user' => 'forge',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($recipeData): void {
            $resource = Mockery::mock(RecipeResource::class);
            $resource->shouldReceive('update')
                ->once()
                ->andReturn($recipeData);
            $mock->shouldReceive('recipes')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(UpdateRecipeTool::class, [
            'recipe_id' => 5,
            'name' => 'Updated Recipe',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Recipe updated successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RecipeResource::class);
            $resource->shouldReceive('update')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('recipes')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(UpdateRecipeTool::class, [
            'recipe_id' => 5,
            'name' => 'Updated Recipe',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('DeleteRecipeTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteRecipeTool::class, []);
        $response->assertHasErrors();
    });

    it('deletes recipe successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RecipeResource::class);
            $resource->shouldReceive('delete')
                ->with(5)
                ->once();
            $mock->shouldReceive('recipes')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteRecipeTool::class, ['recipe_id' => 5]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('deleted successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RecipeResource::class);
            $resource->shouldReceive('delete')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('recipes')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteRecipeTool::class, ['recipe_id' => 5]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('RunRecipeTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(RunRecipeTool::class, []);
        $response->assertHasErrors();
    });

    it('runs recipe successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RecipeResource::class);
            $resource->shouldReceive('run')
                ->once();
            $mock->shouldReceive('recipes')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(RunRecipeTool::class, [
            'recipe_id' => 5,
            'servers' => [1, 2, 3],
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Recipe execution started');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RecipeResource::class);
            $resource->shouldReceive('run')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('recipes')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(RunRecipeTool::class, [
            'recipe_id' => 5,
            'servers' => [1],
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

// ──────────────────────────────────────────────
// RedirectRules
// ──────────────────────────────────────────────

describe('ListRedirectRulesTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListRedirectRulesTool::class, []);
        $response->assertHasErrors();
    });

    it('lists redirect rules successfully', function (): void {
        $ruleData = RedirectRuleData::from([
            'id' => 1,
            'server_id' => 100,
            'site_id' => 200,
            'from' => '/old',
            'to' => '/new',
            'type' => 'redirect',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($ruleData): void {
            $resource = Mockery::mock(RedirectRuleResource::class);
            $resource->shouldReceive('list')
                ->with(100, 200)
                ->once()
                ->andReturn(new RedirectRuleCollectionData(rules: [$ruleData]));
            $mock->shouldReceive('redirectRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListRedirectRulesTool::class, ['server_id' => 100, 'site_id' => 200]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('"count": 1');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RedirectRuleResource::class);
            $resource->shouldReceive('list')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('redirectRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListRedirectRulesTool::class, ['server_id' => 100, 'site_id' => 200]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('GetRedirectRuleTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetRedirectRuleTool::class, []);
        $response->assertHasErrors();
    });

    it('gets redirect rule successfully', function (): void {
        $ruleData = RedirectRuleData::from([
            'id' => 5,
            'server_id' => 100,
            'site_id' => 200,
            'from' => '/old-page',
            'to' => '/new-page',
            'type' => 'permanent',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($ruleData): void {
            $resource = Mockery::mock(RedirectRuleResource::class);
            $resource->shouldReceive('get')
                ->with(100, 200, 5)
                ->once()
                ->andReturn($ruleData);
            $mock->shouldReceive('redirectRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetRedirectRuleTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'rule_id' => 5,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('/old-page');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RedirectRuleResource::class);
            $resource->shouldReceive('get')
                ->once()
                ->andThrow(new Exception('Not found'));
            $mock->shouldReceive('redirectRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetRedirectRuleTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'rule_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('CreateRedirectRuleTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(CreateRedirectRuleTool::class, []);
        $response->assertHasErrors();
    });

    it('creates redirect rule successfully', function (): void {
        $ruleData = RedirectRuleData::from([
            'id' => 10,
            'server_id' => 100,
            'site_id' => 200,
            'from' => '/legacy',
            'to' => '/modern',
            'type' => 'permanent',
            'status' => 'installing',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($ruleData): void {
            $resource = Mockery::mock(RedirectRuleResource::class);
            $resource->shouldReceive('create')
                ->once()
                ->andReturn($ruleData);
            $mock->shouldReceive('redirectRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateRedirectRuleTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'from' => '/legacy',
            'to' => '/modern',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Redirect rule created successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RedirectRuleResource::class);
            $resource->shouldReceive('create')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('redirectRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateRedirectRuleTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'from' => '/legacy',
            'to' => '/modern',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('DeleteRedirectRuleTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteRedirectRuleTool::class, []);
        $response->assertHasErrors();
    });

    it('deletes redirect rule successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RedirectRuleResource::class);
            $resource->shouldReceive('delete')
                ->with(100, 200, 5)
                ->once();
            $mock->shouldReceive('redirectRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteRedirectRuleTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'rule_id' => 5,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Redirect rule deleted successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RedirectRuleResource::class);
            $resource->shouldReceive('delete')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('redirectRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteRedirectRuleTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'rule_id' => 5,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

// ──────────────────────────────────────────────
// Regions
// ──────────────────────────────────────────────

describe('ListRegionsTool', function (): void {
    it('lists regions successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RegionResource::class);
            $resource->shouldReceive('list')
                ->once()
                ->andReturn([['id' => 'nyc1', 'name' => 'New York 1']]);
            $mock->shouldReceive('regions')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListRegionsTool::class, []);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('nyc1');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(RegionResource::class);
            $resource->shouldReceive('list')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('regions')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListRegionsTool::class, []);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

// ──────────────────────────────────────────────
// SSHKeys
// ──────────────────────────────────────────────

describe('ListSSHKeysTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListSSHKeysTool::class, []);
        $response->assertHasErrors();
    });

    it('lists SSH keys successfully', function (): void {
        $keyData = SSHKeyData::from([
            'id' => 1,
            'server_id' => 100,
            'name' => 'deploy-key',
            'status' => 'installed',
            'username' => 'forge',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($keyData): void {
            $resource = Mockery::mock(SSHKeyResource::class);
            $resource->shouldReceive('list')
                ->with(100)
                ->once()
                ->andReturn(new SSHKeyCollectionData(keys: [$keyData]));
            $mock->shouldReceive('sshKeys')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListSSHKeysTool::class, ['server_id' => 100]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('"count": 1');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SSHKeyResource::class);
            $resource->shouldReceive('list')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sshKeys')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListSSHKeysTool::class, ['server_id' => 100]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('GetSSHKeyTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetSSHKeyTool::class, []);
        $response->assertHasErrors();
    });

    it('gets SSH key successfully', function (): void {
        $keyData = SSHKeyData::from([
            'id' => 5,
            'server_id' => 100,
            'name' => 'my-key',
            'status' => 'installed',
            'username' => 'forge',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($keyData): void {
            $resource = Mockery::mock(SSHKeyResource::class);
            $resource->shouldReceive('get')
                ->with(100, 5)
                ->once()
                ->andReturn($keyData);
            $mock->shouldReceive('sshKeys')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetSSHKeyTool::class, ['server_id' => 100, 'key_id' => 5]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('my-key');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SSHKeyResource::class);
            $resource->shouldReceive('get')
                ->once()
                ->andThrow(new Exception('Not found'));
            $mock->shouldReceive('sshKeys')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetSSHKeyTool::class, ['server_id' => 100, 'key_id' => 999]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('CreateSSHKeyTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(CreateSSHKeyTool::class, []);
        $response->assertHasErrors();
    });

    it('creates SSH key successfully', function (): void {
        $keyData = SSHKeyData::from([
            'id' => 10,
            'server_id' => 100,
            'name' => 'new-key',
            'status' => 'installing',
            'username' => 'forge',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($keyData): void {
            $resource = Mockery::mock(SSHKeyResource::class);
            $resource->shouldReceive('create')
                ->once()
                ->andReturn($keyData);
            $mock->shouldReceive('sshKeys')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateSSHKeyTool::class, [
            'server_id' => 100,
            'name' => 'new-key',
            'key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAA...',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('SSH key created successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SSHKeyResource::class);
            $resource->shouldReceive('create')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sshKeys')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateSSHKeyTool::class, [
            'server_id' => 100,
            'name' => 'new-key',
            'key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAA...',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('DeleteSSHKeyTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteSSHKeyTool::class, []);
        $response->assertHasErrors();
    });

    it('deletes SSH key successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SSHKeyResource::class);
            $resource->shouldReceive('delete')
                ->with(100, 5)
                ->once();
            $mock->shouldReceive('sshKeys')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteSSHKeyTool::class, ['server_id' => 100, 'key_id' => 5]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('deleted successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SSHKeyResource::class);
            $resource->shouldReceive('delete')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('sshKeys')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteSSHKeyTool::class, ['server_id' => 100, 'key_id' => 5]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

// ──────────────────────────────────────────────
// SecurityRules
// ──────────────────────────────────────────────

describe('ListSecurityRulesTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListSecurityRulesTool::class, []);
        $response->assertHasErrors();
    });

    it('lists security rules successfully', function (): void {
        $ruleData = SecurityRuleData::from([
            'id' => 1,
            'server_id' => 100,
            'site_id' => 200,
            'name' => 'admin-auth',
            'path' => '/admin',
            'credentials' => 'user:hashed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($ruleData): void {
            $resource = Mockery::mock(SecurityRuleResource::class);
            $resource->shouldReceive('list')
                ->with(100, 200)
                ->once()
                ->andReturn(new SecurityRuleCollectionData(rules: [$ruleData]));
            $mock->shouldReceive('securityRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListSecurityRulesTool::class, ['server_id' => 100, 'site_id' => 200]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('"count": 1');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SecurityRuleResource::class);
            $resource->shouldReceive('list')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('securityRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListSecurityRulesTool::class, ['server_id' => 100, 'site_id' => 200]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('GetSecurityRuleTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetSecurityRuleTool::class, []);
        $response->assertHasErrors();
    });

    it('gets security rule successfully', function (): void {
        $ruleData = SecurityRuleData::from([
            'id' => 5,
            'server_id' => 100,
            'site_id' => 200,
            'name' => 'staging-auth',
            'path' => '/staging',
            'credentials' => 'user:hashed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($ruleData): void {
            $resource = Mockery::mock(SecurityRuleResource::class);
            $resource->shouldReceive('get')
                ->with(100, 200, 5)
                ->once()
                ->andReturn($ruleData);
            $mock->shouldReceive('securityRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetSecurityRuleTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'rule_id' => 5,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('staging-auth');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SecurityRuleResource::class);
            $resource->shouldReceive('get')
                ->once()
                ->andThrow(new Exception('Not found'));
            $mock->shouldReceive('securityRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetSecurityRuleTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'rule_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('CreateSecurityRuleTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(CreateSecurityRuleTool::class, []);
        $response->assertHasErrors();
    });

    it('creates security rule successfully', function (): void {
        $ruleData = SecurityRuleData::from([
            'id' => 10,
            'server_id' => 100,
            'site_id' => 200,
            'name' => 'new-auth',
            'path' => '/protected',
            'credentials' => 'user:hashed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($ruleData): void {
            $resource = Mockery::mock(SecurityRuleResource::class);
            $resource->shouldReceive('create')
                ->once()
                ->andReturn($ruleData);
            $mock->shouldReceive('securityRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateSecurityRuleTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'name' => 'new-auth',
            'path' => '/protected',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Security rule created successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SecurityRuleResource::class);
            $resource->shouldReceive('create')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('securityRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateSecurityRuleTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'name' => 'new-auth',
            'path' => '/protected',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('DeleteSecurityRuleTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteSecurityRuleTool::class, []);
        $response->assertHasErrors();
    });

    it('deletes security rule successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SecurityRuleResource::class);
            $resource->shouldReceive('delete')
                ->with(100, 200, 5)
                ->once();
            $mock->shouldReceive('securityRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteSecurityRuleTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'rule_id' => 5,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Security rule deleted successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(SecurityRuleResource::class);
            $resource->shouldReceive('delete')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('securityRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteSecurityRuleTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'rule_id' => 5,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

// ──────────────────────────────────────────────
// User
// ──────────────────────────────────────────────

describe('GetUserTool', function (): void {
    it('gets user successfully', function (): void {
        $userData = UserData::from([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'card_last_four' => '4242',
            'connected_to_github' => '1',
            'connected_to_gitlab' => '0',
            'connected_to_bitbucket' => '0',
            'connected_to_bitbucket_two' => '0',
            'connected_to_digitalocean' => '1',
            'connected_to_linode' => '0',
            'connected_to_vultr' => '0',
            'connected_to_aws' => '0',
            'connected_to_hetzner' => '0',
            'ready_for_billing' => '1',
            'stripe_is_active' => '1',
            'can_create_servers' => true,
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($userData): void {
            $resource = Mockery::mock(UserResource::class);
            $resource->shouldReceive('get')
                ->once()
                ->andReturn($userData);
            $mock->shouldReceive('user')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetUserTool::class, []);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('John Doe');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(UserResource::class);
            $resource->shouldReceive('get')
                ->once()
                ->andThrow(new Exception('Unauthorized'));
            $mock->shouldReceive('user')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetUserTool::class, []);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

// ──────────────────────────────────────────────
// Webhooks
// ──────────────────────────────────────────────

describe('ListWebhooksTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListWebhooksTool::class, []);
        $response->assertHasErrors();
    });

    it('lists webhooks successfully', function (): void {
        $webhookData = WebhookData::from([
            'id' => 1,
            'server_id' => 100,
            'site_id' => 200,
            'url' => 'https://example.com/webhook',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($webhookData): void {
            $resource = Mockery::mock(WebhookResource::class);
            $resource->shouldReceive('list')
                ->with(100, 200)
                ->once()
                ->andReturn(new WebhookCollectionData(webhooks: [$webhookData]));
            $mock->shouldReceive('webhooks')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListWebhooksTool::class, ['server_id' => 100, 'site_id' => 200]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('"count": 1');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(WebhookResource::class);
            $resource->shouldReceive('list')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('webhooks')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListWebhooksTool::class, ['server_id' => 100, 'site_id' => 200]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('GetWebhookTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetWebhookTool::class, []);
        $response->assertHasErrors();
    });

    it('gets webhook successfully', function (): void {
        $webhookData = WebhookData::from([
            'id' => 5,
            'server_id' => 100,
            'site_id' => 200,
            'url' => 'https://example.com/deploy-hook',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($webhookData): void {
            $resource = Mockery::mock(WebhookResource::class);
            $resource->shouldReceive('get')
                ->with(100, 200, 5)
                ->once()
                ->andReturn($webhookData);
            $mock->shouldReceive('webhooks')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetWebhookTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'webhook_id' => 5,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('deploy-hook');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(WebhookResource::class);
            $resource->shouldReceive('get')
                ->once()
                ->andThrow(new Exception('Not found'));
            $mock->shouldReceive('webhooks')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetWebhookTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'webhook_id' => 999,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('CreateWebhookTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(CreateWebhookTool::class, []);
        $response->assertHasErrors();
    });

    it('creates webhook successfully', function (): void {
        $webhookData = WebhookData::from([
            'id' => 10,
            'server_id' => 100,
            'site_id' => 200,
            'url' => 'https://example.com/new-webhook',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($webhookData): void {
            $resource = Mockery::mock(WebhookResource::class);
            $resource->shouldReceive('create')
                ->once()
                ->andReturn($webhookData);
            $mock->shouldReceive('webhooks')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateWebhookTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'url' => 'https://example.com/new-webhook',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Webhook created successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(WebhookResource::class);
            $resource->shouldReceive('create')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('webhooks')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateWebhookTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'url' => 'https://example.com/new-webhook',
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});

describe('DeleteWebhookTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteWebhookTool::class, []);
        $response->assertHasErrors();
    });

    it('deletes webhook successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(WebhookResource::class);
            $resource->shouldReceive('delete')
                ->with(100, 200, 5)
                ->once();
            $mock->shouldReceive('webhooks')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteWebhookTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'webhook_id' => 5,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": true')
            ->assertSee('deleted successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $resource = Mockery::mock(WebhookResource::class);
            $resource->shouldReceive('delete')
                ->once()
                ->andThrow(new Exception('API Error'));
            $mock->shouldReceive('webhooks')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(DeleteWebhookTool::class, [
            'server_id' => 100,
            'site_id' => 200,
            'webhook_id' => 5,
        ]);

        $response
            ->assertOk()
            ->assertSee('"success": false');
    });
});
