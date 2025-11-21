<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Resource;

class DeploymentGuidelinesResource extends Resource
{
    protected string $name = 'deployment-guidelines';

    protected string $uri = 'forge://docs/deployment';

    protected string $mimeType = 'text/markdown';

    protected string $description = 'Best practices and guidelines for deploying applications with Laravel Forge.';

    public function handle(Request $request): Response
    {
        $content = <<<'MARKDOWN'
        # Deployment Guidelines for Laravel Forge

        ## Pre-Deployment Checklist

        1. **Environment Configuration**
           - Verify `.env` variables are set correctly
           - Check database credentials
           - Confirm cache and session drivers

        2. **Code Review**
           - All tests passing
           - No debug code in production
           - Dependencies up to date

        3. **Database**
           - Migrations ready
           - Backup taken if needed

        ## Deployment Script Best Practices

        ### Standard Laravel Deployment Script

        ```bash
        cd /home/forge/your-site.com
        git pull origin $FORGE_SITE_BRANCH

        $FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

        ( flock -w 10 9 || exit 1
            echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

        if [ -f artisan ]; then
            $FORGE_PHP artisan migrate --force
            $FORGE_PHP artisan config:cache
            $FORGE_PHP artisan route:cache
            $FORGE_PHP artisan view:cache
        fi
        ```

        ### With Frontend Assets

        ```bash
        cd /home/forge/your-site.com
        git pull origin $FORGE_SITE_BRANCH

        $FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

        npm ci
        npm run build

        $FORGE_PHP artisan migrate --force
        $FORGE_PHP artisan config:cache
        $FORGE_PHP artisan route:cache
        $FORGE_PHP artisan view:cache

        ( flock -w 10 9 || exit 1
            echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
        ```

        ## Zero-Downtime Deployment Tips

        1. **Cache Clearing Order**
           - Clear config before caching new config
           - Use atomic cache clearing

        2. **Queue Workers**
           - Restart workers after deployment
           - Use `php artisan queue:restart`

        3. **Maintenance Mode**
           - Only use for major changes
           - `php artisan down --retry=60`

        ## Common Issues

        ### Permissions
        ```bash
        sudo chown -R forge:forge /home/forge/your-site.com
        sudo chmod -R 755 /home/forge/your-site.com/storage
        ```

        ### Composer Memory
        ```bash
        COMPOSER_MEMORY_LIMIT=-1 composer install
        ```

        ### Failed Migrations
        - Always use `--force` in production
        - Test migrations locally first

        ## Quick Deploy

        Enable Quick Deploy for automatic deployments on git push:
        1. Go to Site > Git Repository
        2. Enable "Quick Deploy"
        3. Pushes to the configured branch will auto-deploy

        ## Rollback Strategy

        1. Keep previous release backup
        2. Use git revert for code changes
        3. Have database rollback plan ready
        MARKDOWN;

        return Response::text($content);
    }
}
