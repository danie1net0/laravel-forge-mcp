<?php

declare(strict_types=1);

use App\Integrations\Forge\ForgeClient;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Credentials\ListCredentialsTool;
use App\Mcp\Tools\Git\{CreateDeployKeyTool, DestroyDeployKeyTool, DestroyGitRepositoryTool, InstallGitRepositoryTool, UpdateGitRepositoryTool};
use App\Mcp\Tools\Php\{DisableOpcacheTool, EnableOpcacheTool, InstallPhpTool, ListPhpVersionsTool, UpdatePhpTool};
use App\Mcp\Tools\Backups\{CreateBackupConfigurationTool, DeleteBackupConfigurationTool, DeleteBackupTool, GetBackupConfigurationTool, ListBackupConfigurationsTool, RestoreBackupTool, UpdateBackupConfigurationTool};
use App\Mcp\Tools\Recipes\{CreateRecipeTool, DeleteRecipeTool, GetRecipeTool, ListRecipesTool, RunRecipeTool, UpdateRecipeTool};
use App\Mcp\Tools\SSHKeys\{CreateSSHKeyTool, DeleteSSHKeyTool, GetSSHKeyTool, ListSSHKeysTool};
use App\Mcp\Tools\Commands\{ExecuteSiteCommandTool, GetSiteCommandTool, ListCommandHistoryTool};
use App\Mcp\Tools\Firewall\{CreateFirewallRuleTool, DeleteFirewallRuleTool, GetFirewallRuleTool, ListFirewallRulesTool};
use App\Mcp\Tools\Monitors\{CreateMonitorTool, DeleteMonitorTool, GetMonitorTool, ListMonitorsTool};
use App\Mcp\Tools\Webhooks\{CreateWebhookTool, DeleteWebhookTool, GetWebhookTool, ListWebhooksTool};
use App\Mcp\Tools\Configuration\{GetEnvFileTool, GetNginxConfigTool, UpdateEnvFileTool, UpdateNginxConfigTool};
use App\Mcp\Tools\RedirectRules\{CreateRedirectRuleTool, DeleteRedirectRuleTool, GetRedirectRuleTool, ListRedirectRulesTool};
use App\Mcp\Tools\SecurityRules\{CreateSecurityRuleTool, DeleteSecurityRuleTool, GetSecurityRuleTool, ListSecurityRulesTool};
use App\Mcp\Tools\NginxTemplates\{CreateNginxTemplateTool, DeleteNginxTemplateTool, GetNginxDefaultTemplateTool, GetNginxTemplateTool, ListNginxTemplatesTool, UpdateNginxTemplateTool};
use App\Integrations\Forge\Resources\{BackupResource, CredentialResource, FirewallResource, MonitorResource, NginxTemplateResource, PhpResource, RecipeResource, RedirectRuleResource, RegionResource, SSHKeyResource, SecurityRuleResource, SiteResource, UserResource, WebhookResource};
use App\Integrations\Forge\Data\Backups\{BackupConfigurationCollectionData, BackupConfigurationData};
use App\Integrations\Forge\Data\Recipes\{RecipeCollectionData, RecipeData};
use App\Integrations\Forge\Data\SSHKeys\{SSHKeyCollectionData, SSHKeyData};
use App\Integrations\Forge\Data\Firewall\{FirewallRuleCollectionData, FirewallRuleData};
use App\Integrations\Forge\Data\Monitors\{MonitorCollectionData, MonitorData};
use App\Integrations\Forge\Data\Webhooks\{WebhookCollectionData, WebhookData};
use App\Integrations\Forge\Data\RedirectRules\{RedirectRuleCollectionData, RedirectRuleData};
use App\Integrations\Forge\Data\SecurityRules\{SecurityRuleCollectionData, SecurityRuleData};
use App\Integrations\Forge\Data\NginxTemplates\{NginxTemplateCollectionData, NginxTemplateData};
use App\Mcp\Tools\Regions\ListRegionsTool;
use App\Mcp\Tools\User\GetUserTool;

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

describe('ListFirewallRulesTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListFirewallRulesTool::class, []);
        $response->assertHasErrors();
    });

    it('lists firewall rules successfully', function (): void {
        $mockRule = FirewallRuleData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'SSH', 'port' => 22,
            'ip_address' => null, 'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);
        $collection = new FirewallRuleCollectionData(rules: [$mockRule]);

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $resource = Mockery::mock(FirewallResource::class);
            $resource->shouldReceive('list')->with(1)->once()->andReturn($collection);
            $mock->shouldReceive('firewall')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListFirewallRulesTool::class, ['server_id' => 1]);
        $response->assertOk()->assertSee('SSH');
    });
});

describe('CreateFirewallRuleTool', function (): void {
    it('requires mandatory parameters', function (): void {
        $response = ForgeServer::tool(CreateFirewallRuleTool::class, []);
        $response->assertHasErrors();
    });

    it('creates firewall rule successfully', function (): void {
        $mockRule = FirewallRuleData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'HTTP', 'port' => 80,
            'ip_address' => null, 'status' => 'installing', 'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockRule): void {
            $resource = Mockery::mock(FirewallResource::class);
            $resource->shouldReceive('create')->once()->andReturn($mockRule);
            $mock->shouldReceive('firewall')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(CreateFirewallRuleTool::class, [
            'server_id' => 1, 'name' => 'HTTP', 'port' => '80',
        ]);
        $response->assertOk()->assertSee('"success": true');
    });
});

describe('InstallGitRepositoryTool', function (): void {
    it('requires mandatory parameters', function (): void {
        $response = ForgeServer::tool(InstallGitRepositoryTool::class, []);
        $response->assertHasErrors();
    });

    it('installs git repository successfully', function (): void {
        $mockSite = App\Integrations\Forge\Data\Sites\SiteData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'example.com', 'aliases' => null,
            'directory' => '/public', 'wildcards' => false, 'status' => 'installed',
            'repository' => 'user/repo', 'repository_provider' => 'github',
            'repository_branch' => 'main', 'repository_status' => 'installed',
            'quick_deploy' => false, 'deployment_status' => null, 'project_type' => 'php',
            'app' => null, 'app_status' => null, 'hipchat_room' => null, 'slack_channel' => null,
            'telegram_chat_id' => null, 'telegram_chat_title' => null, 'teams_webhook_url' => null,
            'discord_webhook_url' => null, 'username' => 'forge', 'balancing_status' => null,
            'created_at' => '2024-01-01T00:00:00Z', 'deployment_url' => null, 'is_secured' => false,
            'php_version' => 'php82', 'tags' => [], 'failure_deployment_emails' => null,
            'telegram_secret' => null, 'web_directory' => null,
        ]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockSite): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('installGitRepository')->once()->andReturn($mockSite);
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(InstallGitRepositoryTool::class, [
            'server_id' => 1, 'site_id' => 1, 'provider' => 'github',
            'repository' => 'user/repo', 'branch' => 'main',
        ]);
        $response->assertOk()->assertSee('"success": true');
    });
});

describe('ListSSHKeysTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListSSHKeysTool::class, []);
        $response->assertHasErrors();
    });

    it('lists SSH keys successfully', function (): void {
        $mockKey = SSHKeyData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'my-key', 'status' => 'installed',
            'username' => 'forge', 'created_at' => '2024-01-01T00:00:00Z',
        ]);
        $collection = new SSHKeyCollectionData(keys: [$mockKey]);

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $resource = Mockery::mock(SSHKeyResource::class);
            $resource->shouldReceive('list')->with(1)->once()->andReturn($collection);
            $mock->shouldReceive('sshKeys')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListSSHKeysTool::class, ['server_id' => 1]);
        $response->assertOk()->assertSee('my-key');
    });
});

describe('ListMonitorsTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListMonitorsTool::class, []);
        $response->assertHasErrors();
    });

    it('lists monitors successfully', function (): void {
        $mockMonitor = MonitorData::from([
            'id' => 1, 'server_id' => 1, 'type' => 'disk', 'status' => 'installed',
            'state' => 'ok', 'operator' => '>=', 'threshold' => 90,
            'minutes' => 5, 'state_changed_at' => '2024-01-01T00:00:00Z',
        ]);
        $collection = new MonitorCollectionData(monitors: [$mockMonitor]);

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $resource = Mockery::mock(MonitorResource::class);
            $resource->shouldReceive('list')->with(1)->once()->andReturn($collection);
            $mock->shouldReceive('monitors')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListMonitorsTool::class, ['server_id' => 1]);
        $response->assertOk()->assertSee('disk');
    });
});

describe('ListRecipesTool', function (): void {
    it('lists recipes successfully', function (): void {
        $mockRecipe = RecipeData::from([
            'id' => 1, 'key' => null, 'name' => 'Deploy', 'user' => 'forge',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);
        $collection = new RecipeCollectionData(recipes: [$mockRecipe]);

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $resource = Mockery::mock(RecipeResource::class);
            $resource->shouldReceive('list')->once()->andReturn($collection);
            $mock->shouldReceive('recipes')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListRecipesTool::class, []);
        $response->assertOk()->assertSee('Deploy');
    });
});

describe('ListWebhooksTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(ListWebhooksTool::class, []);
        $response->assertHasErrors();
    });

    it('lists webhooks successfully', function (): void {
        $mockWebhook = WebhookData::from([
            'id' => 1, 'server_id' => 1, 'site_id' => 1,
            'url' => 'https://example.com/webhook', 'created_at' => '2024-01-01T00:00:00Z',
        ]);
        $collection = new WebhookCollectionData(webhooks: [$mockWebhook]);

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $resource = Mockery::mock(WebhookResource::class);
            $resource->shouldReceive('list')->with(1, 1)->once()->andReturn($collection);
            $mock->shouldReceive('webhooks')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListWebhooksTool::class, ['server_id' => 1, 'site_id' => 1]);
        $response->assertOk()->assertSee('webhook');
    });
});

describe('ListRedirectRulesTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(ListRedirectRulesTool::class, []);
        $response->assertHasErrors();
    });

    it('lists redirect rules successfully', function (): void {
        $mockRule = RedirectRuleData::from([
            'id' => 1, 'server_id' => 1, 'site_id' => 1,
            'from' => '/old', 'to' => '/new', 'type' => 'redirect',
            'status' => 'installed', 'created_at' => '2024-01-01T00:00:00Z',
        ]);
        $collection = new RedirectRuleCollectionData(rules: [$mockRule]);

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $resource = Mockery::mock(RedirectRuleResource::class);
            $resource->shouldReceive('list')->with(1, 1)->once()->andReturn($collection);
            $mock->shouldReceive('redirectRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListRedirectRulesTool::class, ['server_id' => 1, 'site_id' => 1]);
        $response->assertOk()->assertSee('/old');
    });
});

describe('ListSecurityRulesTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(ListSecurityRulesTool::class, []);
        $response->assertHasErrors();
    });

    it('lists security rules successfully', function (): void {
        $mockRule = SecurityRuleData::from([
            'id' => 1, 'server_id' => 1, 'site_id' => 1,
            'name' => 'Admin', 'path' => '/admin', 'credentials' => 'user:password',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);
        $collection = new SecurityRuleCollectionData(rules: [$mockRule]);

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $resource = Mockery::mock(SecurityRuleResource::class);
            $resource->shouldReceive('list')->with(1, 1)->once()->andReturn($collection);
            $mock->shouldReceive('securityRules')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListSecurityRulesTool::class, ['server_id' => 1, 'site_id' => 1]);
        $response->assertOk()->assertSee('Admin');
    });
});

describe('ListNginxTemplatesTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListNginxTemplatesTool::class, []);
        $response->assertHasErrors();
    });

    it('lists nginx templates successfully', function (): void {
        $mockTemplate = NginxTemplateData::from([
            'id' => 1, 'server_id' => 1, 'name' => 'Custom', 'content' => 'server {}',
        ]);
        $collection = new NginxTemplateCollectionData(templates: [$mockTemplate]);

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $resource = Mockery::mock(NginxTemplateResource::class);
            $resource->shouldReceive('list')->with(1)->once()->andReturn($collection);
            $mock->shouldReceive('nginxTemplates')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListNginxTemplatesTool::class, ['server_id' => 1]);
        $response->assertOk()->assertSee('Custom');
    });
});

describe('ListBackupConfigurationsTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListBackupConfigurationsTool::class, []);
        $response->assertHasErrors();
    });

    it('lists backup configurations successfully', function (): void {
        $mockConfig = BackupConfigurationData::from([
            'id' => 1, 'server_id' => 1, 'day_of_week' => null, 'time' => '00:00',
            'provider' => 's3', 'provider_name' => 'Amazon S3',
            'last_backup_time' => null, 'created_at' => '2024-01-01T00:00:00Z',
        ]);
        $collection = new BackupConfigurationCollectionData(backups: [$mockConfig]);

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $resource = Mockery::mock(BackupResource::class);
            $resource->shouldReceive('listConfigurations')->with(1)->once()->andReturn($collection);
            $mock->shouldReceive('backups')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListBackupConfigurationsTool::class, ['server_id' => 1]);
        $response->assertOk()->assertSee('s3');
    });
});

describe('GetEnvFileTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetEnvFileTool::class, []);
        $response->assertHasErrors();
    });

    it('gets env file successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('getEnvFile')->with(1, 1)->once()->andReturn('APP_NAME=Laravel');
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetEnvFileTool::class, ['server_id' => 1, 'site_id' => 1]);
        $response->assertOk()->assertSee('APP_NAME');
    });
});

describe('GetNginxConfigTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(GetNginxConfigTool::class, []);
        $response->assertHasErrors();
    });

    it('gets nginx config successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('getNginxConfig')->with(1, 1)->once()->andReturn('server { listen 80; }');
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetNginxConfigTool::class, ['server_id' => 1, 'site_id' => 1]);
        $response->assertOk()->assertSee('listen 80');
    });
});

describe('ListPhpVersionsTool', function (): void {
    it('requires server_id parameter', function (): void {
        $response = ForgeServer::tool(ListPhpVersionsTool::class, []);
        $response->assertHasErrors();
    });

    it('lists PHP versions successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $resource = Mockery::mock(PhpResource::class);
            $resource->shouldReceive('list')->with(1)->once()->andReturn([
                ['version' => 'php84', 'status' => 'installed'],
                ['version' => 'php83', 'status' => 'installed'],
            ]);
            $mock->shouldReceive('php')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListPhpVersionsTool::class, ['server_id' => 1]);
        $response->assertOk()->assertSee('php84');
    });
});

describe('ListRegionsTool', function (): void {
    it('lists regions successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $resource = Mockery::mock(RegionResource::class);
            $resource->shouldReceive('list')->once()->andReturn([
                'ocean2' => ['nyc1' => 'New York 1', 'sfo1' => 'San Francisco 1'],
            ]);
            $mock->shouldReceive('regions')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListRegionsTool::class, []);
        $response->assertOk()->assertSee('nyc1');
    });
});

describe('ListCredentialsTool', function (): void {
    it('lists credentials successfully', function (): void {
        $mockCredential = App\Integrations\Forge\Data\Credentials\CredentialData::from([
            'id' => 1, 'type' => 'ocean2', 'name' => 'DigitalOcean',
        ]);
        $collection = new App\Integrations\Forge\Data\Credentials\CredentialCollectionData(
            credentials: [$mockCredential]
        );

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $resource = Mockery::mock(CredentialResource::class);
            $resource->shouldReceive('list')->once()->andReturn($collection);
            $mock->shouldReceive('credentials')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ListCredentialsTool::class, []);
        $response->assertOk()->assertSee('DigitalOcean');
    });
});

describe('GetUserTool', function (): void {
    it('gets user information successfully', function (): void {
        $mockUser = App\Integrations\Forge\Data\User\UserData::from([
            'id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com',
        ]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockUser): void {
            $resource = Mockery::mock(UserResource::class);
            $resource->shouldReceive('get')->once()->andReturn($mockUser);
            $mock->shouldReceive('user')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(GetUserTool::class, []);
        $response->assertOk()->assertSee('John Doe');
    });
});

describe('ExecuteSiteCommandTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(ExecuteSiteCommandTool::class, []);
        $response->assertHasErrors();
    });

    it('executes command successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $resource = Mockery::mock(SiteResource::class);
            $resource->shouldReceive('executeCommand')->with(1, 1, Mockery::any())->once()->andReturn([
                'id' => 1, 'server_id' => 1, 'site_id' => 1,
                'command' => 'php artisan migrate', 'status' => 'running',
                'created_at' => '2024-01-01T00:00:00Z',
            ]);
            $mock->shouldReceive('sites')->once()->andReturn($resource);
        });

        $response = ForgeServer::tool(ExecuteSiteCommandTool::class, [
            'server_id' => 1, 'site_id' => 1, 'command' => 'php artisan migrate',
        ]);
        $response->assertOk()->assertSee('"success": true');
    });
});

describe('Remaining Tools Structure', function (): void {
    it('all firewall tools can be instantiated', function (): void {
        $tools = [ListFirewallRulesTool::class, GetFirewallRuleTool::class, CreateFirewallRuleTool::class, DeleteFirewallRuleTool::class];

        foreach ($tools as $toolClass) {
            expect(app($toolClass)->name())->toBeString()->not->toBeEmpty();
        }
    });

    it('all git tools can be instantiated', function (): void {
        $tools = [InstallGitRepositoryTool::class, UpdateGitRepositoryTool::class, DestroyGitRepositoryTool::class, CreateDeployKeyTool::class, DestroyDeployKeyTool::class];

        foreach ($tools as $toolClass) {
            expect(app($toolClass)->name())->toBeString()->not->toBeEmpty();
        }
    });

    it('all ssh key tools can be instantiated', function (): void {
        $tools = [ListSSHKeysTool::class, GetSSHKeyTool::class, CreateSSHKeyTool::class, DeleteSSHKeyTool::class];

        foreach ($tools as $toolClass) {
            expect(app($toolClass)->name())->toBeString()->not->toBeEmpty();
        }
    });

    it('all monitor tools can be instantiated', function (): void {
        $tools = [ListMonitorsTool::class, GetMonitorTool::class, CreateMonitorTool::class, DeleteMonitorTool::class];

        foreach ($tools as $toolClass) {
            expect(app($toolClass)->name())->toBeString()->not->toBeEmpty();
        }
    });

    it('all recipe tools can be instantiated', function (): void {
        $tools = [ListRecipesTool::class, GetRecipeTool::class, CreateRecipeTool::class, UpdateRecipeTool::class, DeleteRecipeTool::class, RunRecipeTool::class];

        foreach ($tools as $toolClass) {
            expect(app($toolClass)->name())->toBeString()->not->toBeEmpty();
        }
    });

    it('all webhook tools can be instantiated', function (): void {
        $tools = [ListWebhooksTool::class, GetWebhookTool::class, CreateWebhookTool::class, DeleteWebhookTool::class];

        foreach ($tools as $toolClass) {
            expect(app($toolClass)->name())->toBeString()->not->toBeEmpty();
        }
    });

    it('all redirect rule tools can be instantiated', function (): void {
        $tools = [ListRedirectRulesTool::class, GetRedirectRuleTool::class, CreateRedirectRuleTool::class, DeleteRedirectRuleTool::class];

        foreach ($tools as $toolClass) {
            expect(app($toolClass)->name())->toBeString()->not->toBeEmpty();
        }
    });

    it('all security rule tools can be instantiated', function (): void {
        $tools = [ListSecurityRulesTool::class, GetSecurityRuleTool::class, CreateSecurityRuleTool::class, DeleteSecurityRuleTool::class];

        foreach ($tools as $toolClass) {
            expect(app($toolClass)->name())->toBeString()->not->toBeEmpty();
        }
    });

    it('all nginx template tools can be instantiated', function (): void {
        $tools = [ListNginxTemplatesTool::class, GetNginxTemplateTool::class, GetNginxDefaultTemplateTool::class, CreateNginxTemplateTool::class, UpdateNginxTemplateTool::class, DeleteNginxTemplateTool::class];

        foreach ($tools as $toolClass) {
            expect(app($toolClass)->name())->toBeString()->not->toBeEmpty();
        }
    });

    it('all backup tools can be instantiated', function (): void {
        $tools = [ListBackupConfigurationsTool::class, GetBackupConfigurationTool::class, CreateBackupConfigurationTool::class, UpdateBackupConfigurationTool::class, DeleteBackupConfigurationTool::class, RestoreBackupTool::class, DeleteBackupTool::class];

        foreach ($tools as $toolClass) {
            expect(app($toolClass)->name())->toBeString()->not->toBeEmpty();
        }
    });

    it('all configuration tools can be instantiated', function (): void {
        $tools = [GetEnvFileTool::class, UpdateEnvFileTool::class, GetNginxConfigTool::class, UpdateNginxConfigTool::class];

        foreach ($tools as $toolClass) {
            expect(app($toolClass)->name())->toBeString()->not->toBeEmpty();
        }
    });

    it('all php tools can be instantiated', function (): void {
        $tools = [ListPhpVersionsTool::class, InstallPhpTool::class, UpdatePhpTool::class, EnableOpcacheTool::class, DisableOpcacheTool::class];

        foreach ($tools as $toolClass) {
            expect(app($toolClass)->name())->toBeString()->not->toBeEmpty();
        }
    });

    it('all command tools can be instantiated', function (): void {
        $tools = [ListCommandHistoryTool::class, GetSiteCommandTool::class, ExecuteSiteCommandTool::class];

        foreach ($tools as $toolClass) {
            expect(app($toolClass)->name())->toBeString()->not->toBeEmpty();
        }
    });
});
