<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class SetupLaravelSitePrompt extends Prompt
{
    protected string $name = 'setup-laravel-site';

    protected string $description = 'Complete Laravel site setup from scratch with database, queue workers, and SSL';

    public function handle(Request $request): array
    {
        $serverId = $request->string('server_id', '');
        $domain = $request->string('domain', '');
        $repository = $request->string('repository', '');
        $withHorizon = $request->boolean('with_horizon', false);
        $withScheduler = $request->boolean('with_scheduler', true);

        $workflow = "# Complete Laravel Site Setup\n\n";

        if ($serverId->isEmpty()) {
            $workflow .= <<<'MD'
            ## Step 1: Choose Server

            1. Use `list-servers-tool` to see available servers
            2. Choose a server with:
               - Appropriate PHP version for your Laravel version
               - Sufficient resources (RAM, CPU)
               - Correct region for your users

            MD;
        }

        $workflow .= <<<'MD'
        ## Step 2: Create the Site

        1. Use `create-site-tool` with:
           - server_id: your chosen server
           - domain: your domain name
           - project_type: php
           - directory: /public (Laravel default)
           - php_version: php84 (or your required version)

        2. Wait for site creation to complete

        3. Verify with `get-site-tool` - status should be "installed"

        ## Step 3: Connect Git Repository

        4. Use `install-git-repository-tool`:
           - provider: github, gitlab, bitbucket, or custom
           - repository: your-username/your-repo
           - branch: main (or your default branch)

        5. If repository is private:
           - Use `create-deploy-key-tool` to generate SSH key
           - Add the key to your repository's deploy keys

        ## Step 4: Configure Environment

        6. Use `get-env-tool` to get current environment file

        7. Use `update-env-tool` to configure:
           ```
           APP_NAME=YourAppName
           APP_ENV=production
           APP_DEBUG=false
           APP_URL=https://yourdomain.com

           DB_CONNECTION=mysql
           DB_HOST=127.0.0.1
           DB_PORT=3306
           DB_DATABASE=your_database
           DB_USERNAME=forge
           DB_PASSWORD=your_password

           CACHE_DRIVER=redis
           QUEUE_CONNECTION=redis
           SESSION_DRIVER=redis
           ```

        ## Step 5: Create Database

        8. Use `create-database-tool`:
           - name: your_database (must match DB_DATABASE in .env)

        9. Database user 'forge' is created automatically with server
           - If you need a specific user, use `create-database-user-tool`

        ## Step 6: Configure Deployment Script

        10. Use `get-deployment-script-tool` to see default script

        11. Use `update-deployment-script-tool` with Laravel-optimized script:
            ```bash
            cd /home/forge/yourdomain.com

            git pull origin main

            composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

            npm ci
            npm run build

            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan event:cache
            php artisan icons:cache

            php artisan queue:restart

            ( flock -w 10 9 || exit 1
                echo 'Restarting FPM...'; sudo -S service php8.4-fpm reload ) 9>/tmp/fpmlock
            ```

        ## Step 7: SSL Certificate

        12. Use `obtain-lets-encrypt-certificate-tool`:
            - domains: ["yourdomain.com", "www.yourdomain.com"]

        13. Wait for certificate installation

        14. Verify with `list-certificates-tool`

        MD;

        if ($withScheduler) {
            $workflow .= <<<'MD'

            ## Step 8: Setup Scheduler

            15. Use `create-scheduled-job-tool`:
                - command: php /home/forge/yourdomain.com/artisan schedule:run
                - frequency: every minute (custom: * * * * *)
                - user: forge

            MD;
        }

        if ($withHorizon) {
            $workflow .= <<<'MD'

            ## Step 9: Setup Horizon (Queue Manager)

            16. Use `create-daemon-tool` for Horizon:
                - command: php artisan horizon
                - directory: /home/forge/yourdomain.com
                - processes: 1
                - startsecs: 10

            17. Or use `install-horizon-tool` if available

            MD;
        } else {
            $workflow .= <<<'MD'

            ## Step 9: Setup Queue Worker

            16. Use `create-worker-tool`:
                - connection: redis (or your queue connection)
                - queue: default
                - processes: 2 (adjust based on load)

            MD;
        }

        $workflow .= <<<'MD'

        ## Step 10: First Deployment

        17. Use `deploy-site-tool` to trigger deployment

        18. Use `get-deployment-log-tool` to monitor progress

        19. If deployment fails, use the `troubleshoot-deployment` prompt

        ## Step 11: Post-Deployment Verification

        20. Verify site is accessible via browser
        21. Check database migrations ran correctly
        22. Test queue processing works
        23. Verify scheduler runs (check logs after 1 minute)

        ## Step 12: Setup Monitoring

        24. Use `create-monitor-tool`:
            - type: url
            - url: https://yourdomain.com/health (or /)
            - interval: 1 (minute)

        25. Use `set-deployment-failure-emails-tool` to add notifications

        ## Optional Enhancements

        - **Backups**: Use `create-backup-configuration-tool` for database backups
        - **Redirect Rules**: Use `create-redirect-rule-tool` for www redirects
        - **Security**: Use `create-security-rule-tool` for HTTP auth on staging
        - **Custom Nginx**: Use `update-nginx-template-tool` for custom config

        ## Quick Deploy

        26. Optional: Use `enable-quick-deploy-tool` to auto-deploy on git push

        MD;

        return [Response::text($workflow)->asAssistant()];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'server_id',
                description: 'Server ID to create the site on',
                required: false,
            ),
            new Argument(
                name: 'domain',
                description: 'Domain name for the site',
                required: false,
            ),
            new Argument(
                name: 'repository',
                description: 'Git repository URL or path (e.g., username/repo)',
                required: false,
            ),
            new Argument(
                name: 'with_horizon',
                description: 'Include Laravel Horizon setup (default: false)',
                required: false,
            ),
            new Argument(
                name: 'with_scheduler',
                description: 'Include scheduler/cron setup (default: true)',
                required: false,
            ),
        ];
    }
}
