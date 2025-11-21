<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\{Prompt, Resource, Tool};

class ForgeServer extends Server
{
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
        \App\Mcp\Tools\Sites\ListSitesTool::class,
        \App\Mcp\Tools\Sites\GetSiteTool::class,
        \App\Mcp\Tools\Certificates\ListCertificatesTool::class,
        \App\Mcp\Tools\Databases\ListDatabasesTool::class,
        \App\Mcp\Tools\Jobs\ListScheduledJobsTool::class,
        \App\Mcp\Tools\Daemons\ListDaemonsTool::class,
        \App\Mcp\Tools\Deployments\GetDeploymentLogTool::class,
        \App\Mcp\Tools\Deployments\GetDeploymentScriptTool::class,
        \App\Mcp\Tools\Deployments\DeploySiteTool::class,
        \App\Mcp\Tools\Firewall\ListFirewallRulesTool::class,
        \App\Mcp\Tools\Servers\RebootServerTool::class,
        \App\Mcp\Tools\Certificates\ObtainLetsEncryptCertificateTool::class,
    ];

    /**
     * @var array<int, class-string<Resource>>
     */
    protected array $resources = [
        \App\Mcp\Resources\ForgeApiDocsResource::class,
        \App\Mcp\Resources\DeploymentGuidelinesResource::class,
    ];

    /**
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        \App\Mcp\Prompts\DeployApplicationPrompt::class,
    ];
}
