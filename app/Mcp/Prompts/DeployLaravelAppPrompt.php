<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class DeployLaravelAppPrompt extends Prompt
{
    protected string $name = 'deploy-laravel-app';

    protected string $description = 'Interactive Laravel application deployment workflow with automatic monitoring and error handling';

    public function handle(Request $request): array
    {
        $serverName = $request->string('server_name', '');
        $siteDomain = $request->string('site_domain', '');
        $showLogs = $request->boolean('show_logs', true);

        $workflow = "# Complete Laravel Application Deployment\n\n";

        if ($serverName->isEmpty()) {
            $workflow .= "1. First, use `list-servers-tool` to show available servers\n";
            $workflow .= "2. Ask user which server to use\n\n";
        }

        if ($siteDomain->isEmpty()) {
            $workflow .= "3. Use `list-sites-tool` with the chosen server_id\n";
            $workflow .= "4. Ask user which site to deploy\n\n";
        }

        $workflow .= <<<'MD'
        ## Pre-Deployment Steps

        5. Use `get-deployment-script-tool` to check the deployment script
        6. Show the script to the user and confirm it looks good
        7. If migrations are in the script, warn about potential downtime

        ## Execute Deployment

        8. Use `deploy-site-tool` to trigger the deployment
        9. Wait 2-3 seconds for deployment to start

        ## Monitor Deployment

        10. Use `get-deployment-log-tool` to fetch deployment logs
        11. Show the logs to the user
        12. Look for common error patterns:
            - "composer install failed" → suggest increasing timeout
            - "Migration failed" → suggest checking database connection
            - "npm install failed" → suggest checking node version
            - "Permission denied" → suggest checking file permissions

        ## Post-Deployment

        13. If successful, show deployment time and suggest testing
        14. If failed, show error and suggest fixes based on error type
        MD;

        if ($showLogs) {
            $workflow .= "\n\nNote: Deployment logs will be shown automatically.";
        }

        return [Response::text($workflow)->asAssistant()];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'server_name',
                description: 'Server name or ID (optional, will list if not provided)',
                required: false,
            ),
            new Argument(
                name: 'site_domain',
                description: 'Site domain (optional, will list if not provided)',
                required: false,
            ),
            new Argument(
                name: 'show_logs',
                description: 'Show deployment logs after deploy (default: true)',
                required: false,
            ),
        ];
    }
}
