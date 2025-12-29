<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Resource;

class DeploymentBestPracticesResource extends Resource
{
    protected string $uri = 'forge://best-practices/deployment';

    protected string $name = 'Laravel Deployment Best Practices';

    protected string $description = 'Comprehensive guide for deploying Laravel applications on Forge safely and efficiently';

    protected string $mimeType = 'text/markdown';

    public function handle(Request $request): Response
    {
        $content = <<<'MD'
        # Laravel Deployment Best Practices on Forge

        ## Pre-Deployment Checklist

        Before deploying to production, ensure:

        - ✅ All tests pass locally and in CI/CD
        - ✅ Code reviewed and approved
        - ✅ Tested in staging environment
        - ✅ Environment variables configured in Forge
        - ✅ Database backup created (if migrations included)
        - ✅ Deployment script reviewed and optimized
        - ✅ Team notified of upcoming deployment
        - ✅ Rollback plan prepared

        ## Standard Deployment Script

        The recommended deployment script for Laravel applications:

        ```bash
        cd $FORGE_SITE_PATH

        # Pull latest code
        git pull origin $FORGE_SITE_BRANCH

        # Install dependencies (production mode)
        $FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

        # Restart PHP-FPM with lock to prevent concurrent restarts
        ( flock -w 10 9 || exit 1
            echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

        # Run artisan commands if Laravel detected
        if [ -f artisan ]; then
            # Run migrations (consider maintenance mode for breaking changes)
            $FORGE_PHP artisan migrate --force

            # Optimize application
            $FORGE_PHP artisan config:cache
            $FORGE_PHP artisan route:cache
            $FORGE_PHP artisan view:cache

            # Restart queue workers to load new code
            $FORGE_PHP artisan queue:restart
        fi

        # Build frontend assets (if needed)
        # npm ci && npm run build
        ```

        ## Zero-Downtime Deployments

        For critical applications that cannot have any downtime:

        ### Strategy 1: Queue-Based Migrations

        ```php
        // In deployment script, don't run migrations directly
        // Instead, queue them:
        $FORGE_PHP artisan migrate --force --isolated

        // Migrations run in background while app stays up
        ```

        ### Strategy 2: Database Transactions

        ```php
        // Always use transactions for migrations
        Schema::table('users', function (Blueprint $table) {
            DB::transaction(function () use ($table) {
                $table->string('new_column')->nullable();
            });
        });
        ```

        ### Strategy 3: Blue-Green Deployment

        For mission-critical apps:

        1. Setup two identical environments (blue/green)
        2. Deploy to inactive environment
        3. Test thoroughly
        4. Switch traffic using load balancer
        5. Keep old version for quick rollback

        ## Common Deployment Issues

        ### Issue 1: Composer Timeout

        **Symptom:** Deployment fails with "timeout" or "memory exhausted"

        **Solution:**
        ```bash
        # Increase PHP memory limit
        php -d memory_limit=512M /usr/local/bin/composer install --no-dev

        # Or increase timeout
        COMPOSER_PROCESS_TIMEOUT=600 $FORGE_COMPOSER install --no-dev
        ```

        ### Issue 2: Database Locked During Migration

        **Symptom:** Migration fails with "database is locked" or "timeout"

        **Solution:**
        ```bash
        # Enable maintenance mode before migrations
        $FORGE_PHP artisan down --retry=60

        # Run migrations
        $FORGE_PHP artisan migrate --force

        # Disable maintenance mode
        $FORGE_PHP artisan up
        ```

        ### Issue 3: Queue Workers Not Restarting

        **Symptom:** Workers process old code after deployment

        **Solution:**
        ```bash
        # Ensure queue:restart is in deployment script
        $FORGE_PHP artisan queue:restart

        # Verify workers are configured as daemons in Forge
        # They should automatically restart on this command
        ```

        ### Issue 4: Cache Not Clearing

        **Symptom:** Old configuration/routes/views still used

        **Solution:**
        ```bash
        # Clear all caches before caching new ones
        $FORGE_PHP artisan cache:clear
        $FORGE_PHP artisan config:clear
        $FORGE_PHP artisan route:clear
        $FORGE_PHP artisan view:clear

        # Then cache optimized versions
        $FORGE_PHP artisan config:cache
        $FORGE_PHP artisan route:cache
        $FORGE_PHP artisan view:cache
        ```

        ### Issue 5: Permission Denied Errors

        **Symptom:** Logs show "permission denied" errors

        **Solution:**
        ```bash
        # Fix permissions after deployment
        chmod -R 755 storage bootstrap/cache
        chown -R forge:forge storage bootstrap/cache

        # Ensure web server can write to these directories
        ```

        ## Deployment Strategies

        ### 1. Direct Deploy (Fastest, Most Common)

        ```bash
        git pull → composer install → migrate → cache
        ```

        - ✅ Fast (30-60 seconds)
        - ✅ Simple
        - ❌ Brief downtime possible during migrations

        ### 2. Maintenance Mode Deploy (Safest for Breaking Changes)

        ```bash
        down → git pull → composer → migrate → cache → up
        ```

        - ✅ Prevents user errors during deploy
        - ✅ Safe for database changes
        - ❌ Users see maintenance page (1-2 minutes)

        ### 3. Staged Deploy (Best for Large Apps)

        ```bash
        # Stage 1: Update code
        git pull → composer install

        # Stage 2: Test in background
        artisan test

        # Stage 3: Migrations
        migrate --force

        # Stage 4: Cache and restart
        cache → queue:restart
        ```

        - ✅ Catch errors before they affect users
        - ✅ More control
        - ❌ More complex

        ## Monitoring After Deployment

        After deploying, monitor for:

        ### 1. Application Errors

        ```bash
        # Check Laravel logs
        tail -f storage/logs/laravel.log

        # Check for PHP errors
        tail -f /var/log/nginx/error.log
        ```

        ### 2. Performance Metrics

        - Response times
        - Database query counts
        - Cache hit rates
        - Queue depth

        ### 3. Queue Health

        ```bash
        # Check queue workers
        php artisan queue:monitor

        # Check failed jobs
        php artisan queue:failed
        ```

        ### 4. Resource Usage

        - CPU usage
        - Memory usage
        - Disk space
        - Database connections

        ## Rollback Strategy

        If deployment fails, rollback quickly:

        ### Option 1: Git Rollback

        ```bash
        # Find previous commit
        git log --oneline -5

        # Rollback to previous version
        git reset --hard <previous-commit>

        # Re-run deployment steps
        composer install --no-dev
        php artisan migrate:rollback  # if needed
        php artisan cache:clear
        ```

        ### Option 2: Quick Deploy Previous Version

        ```bash
        # Disable quick deploy first
        # Manually deploy previous Git tag/commit
        # Test thoroughly
        # Re-enable quick deploy
        ```

        ### Option 3: Database Rollback (Careful!)

        ```bash
        # Only if migrations are reversible
        php artisan migrate:rollback --step=1

        # Verify data integrity
        # Consider restoring database backup for complex changes
        ```

        ## Environment-Specific Configurations

        ### Development/Staging

        - Enable debug mode: `APP_DEBUG=true`
        - Use development cache: faster, less optimized
        - Keep query logging on
        - Use development dependencies

        ### Production

        - Disable debug: `APP_DEBUG=false`
        - Enable all caches
        - Optimize autoloader
        - No development dependencies
        - Enable HTTPS enforcement
        - Use production queue drivers (Redis/SQS)

        ## Security Considerations

        ### 1. Environment Variables

        Never commit sensitive data:
        ```bash
        # Configure in Forge UI only
        APP_KEY=
        DB_PASSWORD=
        AWS_SECRET_KEY=
        ```

        ### 2. File Permissions

        ```bash
        # Application files
        chmod -R 755 /path/to/site
        chown -R forge:forge /path/to/site

        # Writable directories only
        chmod -R 775 storage bootstrap/cache
        ```

        ### 3. Deployment Keys

        - Use deploy keys (read-only) not personal keys
        - Rotate keys periodically
        - Different keys per environment

        ## Performance Optimization

        ### 1. Optimize Composer Autoloader

        ```bash
        composer dump-autoload --optimize --no-dev
        ```

        ### 2. Cache Everything

        ```bash
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan event:cache
        ```

        ### 3. Use OPcache

        Ensure OPcache is enabled in PHP:
        ```ini
        opcache.enable=1
        opcache.memory_consumption=256
        opcache.max_accelerated_files=20000
        ```

        ### 4. Queue Long Operations

        Don't run these in web requests:
        - Email sending
        - Image processing
        - API calls to third parties
        - Report generation

        ## Deployment Frequency

        ### Recommended Schedule

        - **Startups:** Multiple times per day
        - **Growing apps:** Once per day
        - **Established apps:** 2-3 times per week
        - **Enterprise:** Weekly with change windows

        ### Best Times to Deploy

        - ✅ During low-traffic hours
        - ✅ Not on Fridays (limited support window)
        - ✅ Not before holidays
        - ✅ With team available for monitoring

        ## Automation Tips

        ### 1. Quick Deploy

        Enable quick deploy for automatic deployments on Git push:
        - Perfect for staging environments
        - Use with caution in production
        - Requires solid CI/CD with tests

        ### 2. Deployment Webhooks

        Setup webhooks to notify team:
        - Slack notification on deploy
        - Email on deployment failure
        - Monitoring tool integration

        ### 3. Health Checks

        Add health check endpoint:
        ```php
        Route::get('/health', function () {
            return response()->json([
                'status' => 'healthy',
                'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
                'cache' => Cache::get('health-check') ? 'working' : 'failed',
                'queue' => Queue::size() < 1000 ? 'healthy' : 'backed-up',
            ]);
        });
        ```

        ## Conclusion

        Successful deployments require:

        1. **Preparation** - Test thoroughly before deploying
        2. **Automation** - Consistent, repeatable process
        3. **Monitoring** - Watch for issues after deploy
        4. **Rollback Plan** - Quick recovery if problems occur
        5. **Documentation** - Record lessons learned

        Remember: A good deployment is invisible. Users should never notice it happened!
        MD;

        return Response::text($content);
    }
}
