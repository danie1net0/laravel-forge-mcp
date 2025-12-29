<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\{Prompt, Resource, Tool};

class ForgeServer extends Server
{
    public int $maxPaginationLength = 100;

    public int $defaultPaginationLength = 100;

    protected string $name = 'Laravel Forge';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'MARKDOWN'
        # Laravel Forge MCP Server

        Manage your Laravel Forge servers, sites, and deployments through the Model Context Protocol.

        ## Authentication

        This server requires a Laravel Forge API token. Set the `FORGE_API_TOKEN` environment variable in your `.env` file:

        ```env
        FORGE_API_TOKEN=your_forge_api_token_here
        ```

        You can generate an API token from your [Forge account settings](https://forge.laravel.com/user-profile/api).

        ## Available Tools

        ### Servers
        - `list-servers-tool` - List all servers in your Forge account
        - `get-server-tool` - Get detailed information about a specific server

        ### Sites
        - `list-sites-tool` - List all sites on a server
        - `get-site-tool` - Get detailed information about a specific site

        ### Deployments
        - Deploy sites and manage deployment scripts

        ### SSL Certificates
        - Manage SSL certificates for your sites

        ### Databases
        - Manage databases and database users

        ### Scheduled Jobs
        - Manage cron jobs on your servers

        ### Daemons
        - Manage long-running processes

        ### Firewall
        - Manage firewall rules

        ## Common Workflows

        ### List All Servers
        ```
        Use the list-servers-tool to see all your Forge servers
        ```

        ### Deploy a Site
        ```
        1. Use get-site-tool to get site information
        2. Use deploy-site-tool to trigger a deployment
        3. Use get-deployment-log-tool to check deployment status
        ```

        ### Setup SSL Certificate
        ```
        1. Use get-site-tool to verify site configuration
        2. Use obtain-lets-encrypt-certificate-tool to install SSL
        ```

        ## Resources

        - **Forge API Documentation** - Complete API reference
        - **Server Templates** - Common server configurations
        - **Deployment Guidelines** - Best practices for deployments

        ## Notes

        - Read-only tools are safe to use and won't modify your infrastructure
        - Destructive operations (create, update, delete) will affect your Forge account
        - Always verify server and site IDs before performing destructive operations
        - Deployments can take several minutes to complete

        ## Support

        - [Forge Documentation](https://forge.laravel.com/docs)
        - [Forge API Documentation](https://forge.laravel.com/api-documentation)
        - [Forge Support](https://forge.laravel.com/support)
    MARKDOWN;

    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        \App\Mcp\Tools\Servers\ListServersTool::class,
        \App\Mcp\Tools\Servers\GetServerTool::class,
        \App\Mcp\Tools\Servers\CreateServerTool::class,
        \App\Mcp\Tools\Servers\UpdateServerTool::class,
        \App\Mcp\Tools\Servers\DeleteServerTool::class,
        \App\Mcp\Tools\Servers\RebootServerTool::class,
        \App\Mcp\Tools\Servers\GetServerLogTool::class,
        \App\Mcp\Tools\Servers\ListEventsTool::class,
        \App\Mcp\Tools\Servers\GetEventOutputTool::class,
        \App\Mcp\Tools\Servers\RevokeServerAccessTool::class,
        \App\Mcp\Tools\Servers\ReconnectServerTool::class,
        \App\Mcp\Tools\Servers\ReactivateServerTool::class,
        \App\Mcp\Tools\Servers\UpdateDatabasePasswordTool::class,
        \App\Mcp\Tools\Sites\ListSitesTool::class,
        \App\Mcp\Tools\Sites\GetSiteTool::class,
        \App\Mcp\Tools\Sites\GetSiteLogTool::class,
        \App\Mcp\Tools\Sites\CreateSiteTool::class,
        \App\Mcp\Tools\Sites\UpdateSiteTool::class,
        \App\Mcp\Tools\Sites\DeleteSiteTool::class,
        \App\Mcp\Tools\Sites\ChangePhpVersionTool::class,
        \App\Mcp\Tools\Sites\ClearSiteLogTool::class,
        \App\Mcp\Tools\Sites\GetLoadBalancingTool::class,
        \App\Mcp\Tools\Sites\GetPackagesAuthTool::class,
        \App\Mcp\Tools\Sites\InstallPhpMyAdminTool::class,
        \App\Mcp\Tools\Sites\InstallWordPressTool::class,
        \App\Mcp\Tools\Sites\ListAliasesTool::class,
        \App\Mcp\Tools\Sites\UninstallPhpMyAdminTool::class,
        \App\Mcp\Tools\Sites\UninstallWordPressTool::class,
        \App\Mcp\Tools\Sites\UpdateAliasesTool::class,
        \App\Mcp\Tools\Sites\UpdateLoadBalancingTool::class,
        \App\Mcp\Tools\Sites\UpdatePackagesAuthTool::class,
        \App\Mcp\Tools\Git\InstallGitRepositoryTool::class,
        \App\Mcp\Tools\Git\UpdateGitRepositoryTool::class,
        \App\Mcp\Tools\Git\DestroyGitRepositoryTool::class,
        \App\Mcp\Tools\Git\CreateDeployKeyTool::class,
        \App\Mcp\Tools\Git\DestroyDeployKeyTool::class,
        \App\Mcp\Tools\Certificates\ListCertificatesTool::class,
        \App\Mcp\Tools\Certificates\GetCertificateTool::class,
        \App\Mcp\Tools\Certificates\GetCertificateSigningRequestTool::class,
        \App\Mcp\Tools\Certificates\ObtainLetsEncryptCertificateTool::class,
        \App\Mcp\Tools\Certificates\InstallCertificateTool::class,
        \App\Mcp\Tools\Certificates\DeleteCertificateTool::class,
        \App\Mcp\Tools\Certificates\ActivateCertificateTool::class,
        \App\Mcp\Tools\Databases\ListDatabasesTool::class,
        \App\Mcp\Tools\Databases\GetDatabaseTool::class,
        \App\Mcp\Tools\Databases\CreateDatabaseTool::class,
        \App\Mcp\Tools\Databases\DeleteDatabaseTool::class,
        \App\Mcp\Tools\Databases\ListDatabaseUsersTool::class,
        \App\Mcp\Tools\Databases\GetDatabaseUserTool::class,
        \App\Mcp\Tools\Databases\CreateDatabaseUserTool::class,
        \App\Mcp\Tools\Databases\UpdateDatabaseUserTool::class,
        \App\Mcp\Tools\Databases\DeleteDatabaseUserTool::class,
        \App\Mcp\Tools\Databases\SyncDatabaseTool::class,
        \App\Mcp\Tools\Jobs\ListScheduledJobsTool::class,
        \App\Mcp\Tools\Jobs\GetScheduledJobTool::class,
        \App\Mcp\Tools\Jobs\CreateScheduledJobTool::class,
        \App\Mcp\Tools\Jobs\DeleteScheduledJobTool::class,
        \App\Mcp\Tools\Jobs\GetJobOutputTool::class,
        \App\Mcp\Tools\Daemons\ListDaemonsTool::class,
        \App\Mcp\Tools\Daemons\GetDaemonTool::class,
        \App\Mcp\Tools\Daemons\CreateDaemonTool::class,
        \App\Mcp\Tools\Daemons\RestartDaemonTool::class,
        \App\Mcp\Tools\Daemons\DeleteDaemonTool::class,
        \App\Mcp\Tools\Commands\ListCommandHistoryTool::class,
        \App\Mcp\Tools\Commands\GetSiteCommandTool::class,
        \App\Mcp\Tools\Commands\ExecuteSiteCommandTool::class,
        \App\Mcp\Tools\Deployments\GetDeploymentLogTool::class,
        \App\Mcp\Tools\Deployments\GetDeploymentScriptTool::class,
        \App\Mcp\Tools\Deployments\UpdateDeploymentScriptTool::class,
        \App\Mcp\Tools\Deployments\EnableQuickDeployTool::class,
        \App\Mcp\Tools\Deployments\DisableQuickDeployTool::class,
        \App\Mcp\Tools\Deployments\ListDeploymentHistoryTool::class,
        \App\Mcp\Tools\Deployments\GetDeploymentHistoryDeploymentTool::class,
        \App\Mcp\Tools\Deployments\GetDeploymentHistoryOutputTool::class,
        \App\Mcp\Tools\Deployments\DeploySiteTool::class,
        \App\Mcp\Tools\Deployments\ResetDeploymentStateTool::class,
        \App\Mcp\Tools\Deployments\SetDeploymentFailureEmailsTool::class,
        \App\Mcp\Tools\Firewall\ListFirewallRulesTool::class,
        \App\Mcp\Tools\Firewall\GetFirewallRuleTool::class,
        \App\Mcp\Tools\Firewall\CreateFirewallRuleTool::class,
        \App\Mcp\Tools\Firewall\DeleteFirewallRuleTool::class,
        \App\Mcp\Tools\Workers\ListWorkersTool::class,
        \App\Mcp\Tools\Workers\GetWorkerTool::class,
        \App\Mcp\Tools\Workers\CreateWorkerTool::class,
        \App\Mcp\Tools\Workers\RestartWorkerTool::class,
        \App\Mcp\Tools\Workers\DeleteWorkerTool::class,
        \App\Mcp\Tools\Workers\GetWorkerOutputTool::class,
        \App\Mcp\Tools\Services\InstallBlackfireTool::class,
        \App\Mcp\Tools\Services\InstallPapertrailTool::class,
        \App\Mcp\Tools\Services\RebootMysqlTool::class,
        \App\Mcp\Tools\Services\RebootNginxTool::class,
        \App\Mcp\Tools\Services\RebootPhpTool::class,
        \App\Mcp\Tools\Services\RebootPostgresTool::class,
        \App\Mcp\Tools\Services\RemoveBlackfireTool::class,
        \App\Mcp\Tools\Services\RemovePapertrailTool::class,
        \App\Mcp\Tools\Services\RestartServiceTool::class,
        \App\Mcp\Tools\Services\StartServiceTool::class,
        \App\Mcp\Tools\Services\StopMysqlTool::class,
        \App\Mcp\Tools\Services\StopNginxTool::class,
        \App\Mcp\Tools\Services\StopPostgresTool::class,
        \App\Mcp\Tools\Services\StopServiceTool::class,
        \App\Mcp\Tools\Services\TestNginxTool::class,
        \App\Mcp\Tools\Credentials\ListCredentialsTool::class,
        \App\Mcp\Tools\Monitors\ListMonitorsTool::class,
        \App\Mcp\Tools\Monitors\GetMonitorTool::class,
        \App\Mcp\Tools\Monitors\CreateMonitorTool::class,
        \App\Mcp\Tools\Monitors\DeleteMonitorTool::class,
        \App\Mcp\Tools\Recipes\ListRecipesTool::class,
        \App\Mcp\Tools\Recipes\GetRecipeTool::class,
        \App\Mcp\Tools\Recipes\CreateRecipeTool::class,
        \App\Mcp\Tools\Recipes\UpdateRecipeTool::class,
        \App\Mcp\Tools\Recipes\DeleteRecipeTool::class,
        \App\Mcp\Tools\Recipes\RunRecipeTool::class,
        \App\Mcp\Tools\SSHKeys\ListSSHKeysTool::class,
        \App\Mcp\Tools\SSHKeys\GetSSHKeyTool::class,
        \App\Mcp\Tools\SSHKeys\CreateSSHKeyTool::class,
        \App\Mcp\Tools\SSHKeys\DeleteSSHKeyTool::class,
        \App\Mcp\Tools\Webhooks\ListWebhooksTool::class,
        \App\Mcp\Tools\Webhooks\GetWebhookTool::class,
        \App\Mcp\Tools\Webhooks\CreateWebhookTool::class,
        \App\Mcp\Tools\Webhooks\DeleteWebhookTool::class,
        \App\Mcp\Tools\RedirectRules\ListRedirectRulesTool::class,
        \App\Mcp\Tools\RedirectRules\GetRedirectRuleTool::class,
        \App\Mcp\Tools\RedirectRules\CreateRedirectRuleTool::class,
        \App\Mcp\Tools\RedirectRules\DeleteRedirectRuleTool::class,
        \App\Mcp\Tools\SecurityRules\ListSecurityRulesTool::class,
        \App\Mcp\Tools\SecurityRules\GetSecurityRuleTool::class,
        \App\Mcp\Tools\SecurityRules\CreateSecurityRuleTool::class,
        \App\Mcp\Tools\SecurityRules\DeleteSecurityRuleTool::class,
        \App\Mcp\Tools\NginxTemplates\ListNginxTemplatesTool::class,
        \App\Mcp\Tools\NginxTemplates\GetNginxTemplateTool::class,
        \App\Mcp\Tools\NginxTemplates\GetNginxDefaultTemplateTool::class,
        \App\Mcp\Tools\NginxTemplates\CreateNginxTemplateTool::class,
        \App\Mcp\Tools\NginxTemplates\UpdateNginxTemplateTool::class,
        \App\Mcp\Tools\NginxTemplates\DeleteNginxTemplateTool::class,
        \App\Mcp\Tools\Backups\ListBackupConfigurationsTool::class,
        \App\Mcp\Tools\Backups\GetBackupConfigurationTool::class,
        \App\Mcp\Tools\Backups\CreateBackupConfigurationTool::class,
        \App\Mcp\Tools\Backups\UpdateBackupConfigurationTool::class,
        \App\Mcp\Tools\Backups\DeleteBackupConfigurationTool::class,
        \App\Mcp\Tools\Backups\RestoreBackupTool::class,
        \App\Mcp\Tools\Backups\DeleteBackupTool::class,
        \App\Mcp\Tools\Configuration\GetEnvFileTool::class,
        \App\Mcp\Tools\Configuration\UpdateEnvFileTool::class,
        \App\Mcp\Tools\Configuration\GetNginxConfigTool::class,
        \App\Mcp\Tools\Configuration\UpdateNginxConfigTool::class,
        \App\Mcp\Tools\Php\ListPhpVersionsTool::class,
        \App\Mcp\Tools\Php\InstallPhpTool::class,
        \App\Mcp\Tools\Php\UpdatePhpTool::class,
        \App\Mcp\Tools\Php\EnableOpcacheTool::class,
        \App\Mcp\Tools\Php\DisableOpcacheTool::class,
        \App\Mcp\Tools\Regions\ListRegionsTool::class,
        \App\Mcp\Tools\User\GetUserTool::class,
        \App\Mcp\Tools\Integrations\GetHorizonTool::class,
        \App\Mcp\Tools\Integrations\EnableHorizonTool::class,
        \App\Mcp\Tools\Integrations\DisableHorizonTool::class,
        \App\Mcp\Tools\Integrations\GetOctaneTool::class,
        \App\Mcp\Tools\Integrations\EnableOctaneTool::class,
        \App\Mcp\Tools\Integrations\DisableOctaneTool::class,
        \App\Mcp\Tools\Integrations\GetReverbTool::class,
        \App\Mcp\Tools\Integrations\EnableReverbTool::class,
        \App\Mcp\Tools\Integrations\DisableReverbTool::class,
        \App\Mcp\Tools\Integrations\GetPulseTool::class,
        \App\Mcp\Tools\Integrations\EnablePulseTool::class,
        \App\Mcp\Tools\Integrations\DisablePulseTool::class,
        \App\Mcp\Tools\Integrations\GetInertiaTool::class,
        \App\Mcp\Tools\Integrations\EnableInertiaTool::class,
        \App\Mcp\Tools\Integrations\DisableInertiaTool::class,
        \App\Mcp\Tools\Integrations\GetMaintenanceTool::class,
        \App\Mcp\Tools\Integrations\EnableMaintenanceTool::class,
        \App\Mcp\Tools\Integrations\DisableMaintenanceTool::class,
        \App\Mcp\Tools\Integrations\GetSchedulerTool::class,
        \App\Mcp\Tools\Integrations\EnableSchedulerTool::class,
        \App\Mcp\Tools\Integrations\DisableSchedulerTool::class,
        \App\Mcp\Tools\Composite\ServerHealthCheckTool::class,
        \App\Mcp\Tools\Composite\SiteStatusDashboardTool::class,
        \App\Mcp\Tools\Composite\BulkDeployTool::class,
        \App\Mcp\Tools\Composite\SSLExpirationCheckTool::class,
        \App\Mcp\Tools\Composite\CloneSiteTool::class,
    ];

    /**
     * @var array<int, class-string<Resource>>
     */
    protected array $resources = [
        \App\Mcp\Resources\ForgeApiDocsResource::class,
        \App\Mcp\Resources\DeploymentGuidelinesResource::class,
        \App\Mcp\Resources\DeploymentBestPracticesResource::class,
        \App\Mcp\Resources\TroubleshootingGuideResource::class,
        \App\Mcp\Resources\SecurityBestPracticesResource::class,
        \App\Mcp\Resources\PHPUpgradeGuideResource::class,
        \App\Mcp\Resources\QueueWorkerGuideResource::class,
        \App\Mcp\Resources\NginxOptimizationResource::class,
        \App\Mcp\Resources\SecurityHardeningResource::class,
    ];

    /**
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        \App\Mcp\Prompts\DeployLaravelAppPrompt::class,
        \App\Mcp\Prompts\SetupNewServerPrompt::class,
        \App\Mcp\Prompts\MigrateSitePrompt::class,
        \App\Mcp\Prompts\TroubleshootDeploymentPrompt::class,
        \App\Mcp\Prompts\SSLRenewalPrompt::class,
        \App\Mcp\Prompts\SetupLaravelSitePrompt::class,
    ];
}
