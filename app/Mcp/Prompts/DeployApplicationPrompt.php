<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class DeployApplicationPrompt extends Prompt
{
    protected string $name = 'deploy-application';

    protected string $description = 'Guide for deploying a Laravel application to a Forge server.';

    public function handle(Request $request): array
    {
        $serverId = $request->string('server_id');
        $siteId = $request->string('site_id');
        $runMigrations = $request->boolean('run_migrations', true);

        $migrationStep = $runMigrations
            ? '- Database migrations will be executed automatically'
            : '- Database migrations will NOT be run (manual migration required)';

        return [
            Response::text(<<<MARKDOWN
            # Deployment Guide

            You are about to deploy an application to Laravel Forge.

            ## Pre-Deployment Checklist

            1. **Verify the target**
               - Server ID: {$serverId}
               - Site ID: {$siteId}

            2. **Before deploying, ensure:**
               - All tests are passing locally
               - Code has been reviewed and approved
               - Database backup has been taken (if needed)
               - Environment variables are correctly configured

            3. **Deployment will:**
               - Pull the latest code from the configured branch
               - Install Composer dependencies
               - Build frontend assets (if configured)
               {$migrationStep}
               - Clear and rebuild caches
               - Restart PHP-FPM

            ## Deployment Steps

            1. First, use `get-site-tool` with server_id={$serverId} and site_id={$siteId} to verify the current site configuration.

            2. Review the current deployment script using `get-deployment-script-tool`.

            3. When ready, trigger the deployment using `deploy-site-tool` with the server and site IDs.

            4. Monitor the deployment using `get-deployment-log-tool` to check for any errors.

            ## Rollback Plan

            If the deployment fails:
            1. Check the deployment log for errors
            2. Use git revert or manual deployment to restore previous version
            3. Consider restoring database from backup if migrations caused issues

            Would you like me to proceed with verifying the site configuration?
            MARKDOWN)->asAssistant(),
        ];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'server_id',
                description: 'The unique ID of the Forge server',
                required: true,
            ),
            new Argument(
                name: 'site_id',
                description: 'The unique ID of the site to deploy',
                required: true,
            ),
            new Argument(
                name: 'run_migrations',
                description: 'Whether to run database migrations (default: true)',
                required: false,
            ),
        ];
    }
}
