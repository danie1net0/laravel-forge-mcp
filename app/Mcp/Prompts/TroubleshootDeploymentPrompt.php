<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class TroubleshootDeploymentPrompt extends Prompt
{
    protected string $name = 'troubleshoot-deployment';

    protected string $description = 'Diagnose and fix deployment failures with step-by-step troubleshooting';

    public function handle(Request $request): array
    {
        $serverId = $request->string('server_id', '');
        $siteId = $request->string('site_id', '');
        $errorType = $request->string('error_type', '');

        $workflow = "# Deployment Troubleshooting Workflow\n\n";

        if ($serverId->isEmpty() || $siteId->isEmpty()) {
            $workflow .= <<<'MD'
            ## Step 1: Identify the Failed Deployment

            1. Use `list-servers-tool` to find the server
            2. Use `list-sites-tool` to find the site
            3. Use `list-deployment-history-tool` to see recent deployments

            MD;
        }

        $workflow .= <<<'MD'
        ## Step 2: Gather Deployment Information

        1. Use `get-deployment-log-tool` to fetch the latest deployment log
        2. Use `get-deployment-history-output-tool` for detailed output
        3. Look for error patterns in the logs

        ## Step 3: Common Error Patterns and Solutions

        ### Composer Errors

        **"composer install failed"**
        - Check PHP memory limit: may need to increase in php.ini
        - Check if private packages need authentication
        - Use `get-packages-auth-tool` to verify Composer auth
        - Try running with --no-dev flag in production

        **"Cannot allocate memory"**
        - Server needs more RAM or swap space
        - Consider adding swap via recipe

        **"Could not find package"**
        - Private repository not configured
        - Use `update-packages-auth-tool` to add credentials

        ### NPM/Node Errors

        **"npm install failed" or "ENOENT"**
        - Check Node.js version on server
        - Clear npm cache: `rm -rf node_modules && npm cache clean --force`
        - Check for missing native dependencies

        **"JavaScript heap out of memory"**
        - Add to deployment script: `export NODE_OPTIONS="--max-old-space-size=4096"`

        ### Permission Errors

        **"Permission denied"**
        - Storage/cache directories not writable
        - Add to deployment script:
          ```bash
          chmod -R 775 storage bootstrap/cache
          chown -R forge:www-data storage bootstrap/cache
          ```

        ### Database/Migration Errors

        **"Migration failed"**
        - Check database connection in .env
        - Use `get-env-tool` to verify credentials
        - Check if database exists: `list-databases-tool`
        - Consider using `--force` flag in production

        **"Table already exists"**
        - Previous migration partially ran
        - May need to manually fix migration state
        - Use `execute-site-command-tool` to run artisan commands

        ### Git/Repository Errors

        **"Repository not found" or "Permission denied (publickey)"**
        - SSH key not deployed to repository
        - Use `create-deploy-key-tool` to generate new key
        - Add key to GitHub/GitLab/Bitbucket

        **"Could not resolve host"**
        - DNS issue on server
        - Try using IP instead of hostname temporarily

        ### PHP Errors

        **"PHP Fatal error: Allowed memory size exhausted"**
        - Increase memory_limit in php.ini
        - May need to restart PHP-FPM after changes

        **"Class not found"**
        - Run `composer dump-autoload` in deployment script
        - Clear config/route cache

        ## Step 4: Fix and Redeploy

        4. If deployment script needs changes:
           - Use `get-deployment-script-tool` to get current script
           - Use `update-deployment-script-tool` to fix issues

        5. If environment variables need updating:
           - Use `get-env-tool` to check current values
           - Use `update-env-tool` to fix issues

        6. If deployment is stuck:
           - Use `reset-deployment-state-tool` to reset state
           - Then redeploy with `deploy-site-tool`

        ## Step 5: Verify Fix

        7. Use `deploy-site-tool` to trigger new deployment
        8. Use `get-deployment-log-tool` to verify success
        9. Check the site is working correctly

        ## Step 6: Prevent Future Issues

        10. Set up deployment failure notifications:
            - Use `set-deployment-failure-emails-tool` to add email alerts

        11. Consider adding health checks:
            - Use `create-monitor-tool` to add uptime monitoring

        ## Emergency Recovery

        If site is completely broken:
        1. Check if there's a working backup
        2. Use `get-site-log-tool` to check for PHP errors
        3. Consider rolling back to previous commit
        4. Check server logs for system-level issues

        MD;

        if ($errorType->isNotEmpty()) {
            $workflow .= "\n## Specific Error Focus: {$errorType->value()}\n";
            $workflow .= "Focus on the \"{$errorType->value()}\" section above for targeted troubleshooting.\n";
        }

        return [Response::text($workflow)->asAssistant()];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'server_id',
                description: 'Server ID where deployment failed',
                required: false,
            ),
            new Argument(
                name: 'site_id',
                description: 'Site ID where deployment failed',
                required: false,
            ),
            new Argument(
                name: 'error_type',
                description: 'Type of error: composer, npm, permission, database, git, php',
                required: false,
            ),
        ];
    }
}
