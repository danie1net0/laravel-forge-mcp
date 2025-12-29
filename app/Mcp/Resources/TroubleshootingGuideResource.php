<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Resource;

class TroubleshootingGuideResource extends Resource
{
    protected string $uri = 'forge://troubleshooting/common-errors';

    protected string $name = 'Common Forge Issues & Solutions';

    protected string $description = 'Quick reference guide for diagnosing and fixing common Laravel Forge problems';

    protected string $mimeType = 'text/markdown';

    public function handle(Request $request): Response
    {
        $content = <<<'MD'
        # Laravel Forge Troubleshooting Guide

        Quick solutions to the most common Forge problems.

        ## Site Issues

        ### Site Shows "Default Forge Page"

        **Symptoms:**
        - Site displays default Forge welcome page
        - Should show your application

        **Causes:**
        1. Git repository not deployed yet
        2. Incorrect web directory configured
        3. DNS not pointed to server

        **Solutions:**
        ```bash
        # Check if code exists
        ls -la /home/forge/{domain}

        # If empty, deploy site
        # Use deploy-site-tool or trigger deployment

        # Check web directory setting
        # Laravel should use: /public
        # Static sites: / or /dist or /out

        # Verify DNS
        dig {domain} +short
        # Should return your server IP
        ```

        ### 502 Bad Gateway Error

        **Symptoms:**
        - Nginx shows "502 Bad Gateway"
        - Site was working, now broken

        **Causes:**
        1. PHP-FPM crashed or not running
        2. Socket file missing/wrong
        3. Out of memory

        **Solutions:**
        ```bash
        # Restart PHP-FPM
        sudo service php8.2-fpm restart

        # Check if PHP-FPM is running
        sudo service php8.2-fpm status

        # Check PHP-FPM logs
        sudo tail -f /var/log/php8.2-fpm.log

        # Check memory usage
        free -h

        # If out of memory, restart server or upgrade
        ```

        ### 500 Internal Server Error

        **Symptoms:**
        - Site shows "500 Internal Server Error"
        - No specific error message

        **Causes:**
        1. PHP syntax error
        2. Missing .env file
        3. Wrong file permissions
        4. Database connection failed

        **Solutions:**
        ```bash
        # Check Laravel logs
        tail -100 /home/forge/{domain}/storage/logs/laravel.log

        # Check nginx error log
        sudo tail -50 /var/log/nginx/{domain}-error.log

        # Fix permissions
        chmod -R 755 /home/forge/{domain}
        chmod -R 775 /home/forge/{domain}/storage
        chmod -R 775 /home/forge/{domain}/bootstrap/cache

        # Verify .env exists
        ls -la /home/forge/{domain}/.env

        # Test database connection
        php artisan tinker
        >>> DB::connection()->getPdo();
        ```

        ### Site Loading Slowly

        **Symptoms:**
        - Pages take 5-30+ seconds to load
        - Eventually might timeout

        **Causes:**
        1. Database queries not optimized
        2. No caching enabled
        3. Debug mode on in production
        4. Memory issues

        **Solutions:**
        ```bash
        # Check if debug is enabled (should be false in production)
        grep APP_DEBUG /home/forge/{domain}/.env

        # Enable all caches
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache

        # Check database indexes
        php artisan db:show

        # Install query monitor
        composer require barryvdh/laravel-debugbar --dev
        ```

        ## Deployment Issues

        ### Deployment Stuck/Hangs

        **Symptoms:**
        - Deployment shows "deploying" for 10+ minutes
        - Never completes or fails

        **Causes:**
        1. Composer hanging on package download
        2. NPM install frozen
        3. Migration waiting for input
        4. Script has infinite loop

        **Solutions:**
        ```bash
        # Check what's running
        ps aux | grep deploy

        # Kill stuck deployment
        kill -9 {process-id}

        # Check deployment script for:
        # - Interactive prompts (use --no-interaction)
        # - Missing timeouts
        # - while/for loops without breaks

        # Increase timeouts in deployment script
        COMPOSER_PROCESS_TIMEOUT=600 composer install
        ```

        ### Composer Install Fails

        **Error Messages:**
        ```
        Fatal error: Allowed memory size of 1610612736 bytes exhausted
        ```

        **Solution:**
        ```bash
        # Increase PHP memory in deployment script
        php -d memory_limit=512M /usr/local/bin/composer install --no-dev --no-interaction

        # Or update deployment script to:
        COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev
        ```

        **Error Messages:**
        ```
        Your requirements could not be resolved to an installable set of packages
        ```

        **Solution:**
        ```bash
        # Check PHP version matches composer.json
        php -v

        # Update dependencies locally first
        composer update

        # Commit updated composer.lock
        git add composer.lock && git commit -m "Update dependencies"

        # Deploy again
        ```

        ### Migration Fails

        **Error Messages:**
        ```
        SQLSTATE[42S01]: Base table or view already exists
        ```

        **Solution:**
        ```bash
        # Check migration status
        php artisan migrate:status

        # If migration already ran, mark it as complete
        # Or rollback and re-run
        php artisan migrate:rollback --step=1
        php artisan migrate --force
        ```

        **Error Messages:**
        ```
        SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded
        ```

        **Solution:**
        ```bash
        # Another process has locked the database
        # Check for running migrations
        ps aux | grep migrate

        # Check database locks
        SHOW PROCESSLIST;  # in MySQL

        # Kill long-running queries if safe
        # Then retry migration
        ```

        ### Git Pull Fails

        **Error Messages:**
        ```
        Permission denied (publickey)
        fatal: Could not read from remote repository
        ```

        **Solution:**
        ```bash
        # Deploy key not added or invalid
        # Regenerate deploy key using create-deploy-key-tool
        # Add public key to GitHub/GitLab/Bitbucket

        # Test SSH connection
        ssh -T git@github.com
        # Should show authentication success
        ```

        **Error Messages:**
        ```
        error: Your local changes would be overwritten by merge
        ```

        **Solution:**
        ```bash
        # Local changes exist on server
        # Either commit them or reset

        # See what changed
        git status

        # Discard local changes (careful!)
        git reset --hard HEAD
        git clean -fd

        # Then pull again
        git pull origin main
        ```

        ## Queue Issues

        ### Jobs Not Processing

        **Symptoms:**
        - Jobs stuck in queue
        - Queue depth growing
        - Workers show running but not processing

        **Causes:**
        1. Workers not running
        2. Wrong queue connection
        3. Redis/database down

        **Solutions:**
        ```bash
        # Check workers status
        php artisan queue:monitor

        # Check if workers exist in Forge
        # Use list-workers-tool

        # Test queue manually
        php artisan queue:work --once --verbose

        # Check Redis connection
        redis-cli ping
        # Should return "PONG"

        # Restart workers
        # Use restart-worker-tool for each worker
        ```

        ### Failed Jobs Piling Up

        **Symptoms:**
        - `failed_jobs` table growing
        - Jobs failing repeatedly

        **Causes:**
        1. Bug in job code
        2. External API down
        3. Database constraint violation

        **Solutions:**
        ```bash
        # List failed jobs
        php artisan queue:failed

        # See error for specific job
        php artisan queue:failed {job-id}

        # Retry all failed jobs (after fixing cause)
        php artisan queue:retry all

        # Or delete failed jobs
        php artisan queue:flush

        # Prevent future failures
        # - Add better error handling
        # - Increase timeout
        # - Use exponential backoff
        ```

        ### Workers Using Too Much Memory

        **Symptoms:**
        - Server running out of memory
        - Workers consuming 500MB+ each

        **Causes:**
        1. Memory leaks in application
        2. Workers running too long
        3. Too many workers

        **Solutions:**
        ```bash
        # Limit jobs per worker
        # Update worker configuration:
        # --max-jobs=1000

        # Limit time per worker
        # --max-time=3600

        # Reduce number of workers
        # Use delete-worker-tool to remove excess

        # Fix memory leaks in code
        # - Unset large variables
        # - Clear collections after processing
        # - Avoid loading all records at once
        ```

        ## SSL/Certificate Issues

        ### Let's Encrypt Fails

        **Error Messages:**
        ```
        DNS validation failed
        ```

        **Solution:**
        ```bash
        # DNS not pointing to server
        dig {domain} +short
        # Must return server IP

        # Wait for DNS propagation (can take 24-48 hours)
        # Try again after DNS is correct
        ```

        **Error Messages:**
        ```
        Rate limit exceeded
        ```

        **Solution:**
        ```bash
        # Let's Encrypt limits certificates per domain
        # Limits:
        # - 50 certificates per domain per week
        # - 5 duplicate certificates per week

        # Wait 1 week for limit to reset
        # Or use staging environment for testing
        ```

        **Error Messages:**
        ```
        Connection refused on port 80
        ```

        **Solution:**
        ```bash
        # Port 80 must be accessible for HTTP challenge
        # Check firewall rules
        # Use list-firewall-rules-tool

        # Ensure port 80 is open
        # Use create-firewall-rule-tool if needed
        ```

        ### Certificate Not Auto-Renewing

        **Symptoms:**
        - Certificate expired
        - Renewal didn't happen automatically

        **Causes:**
        1. Forge renewal cron failed
        2. Domain no longer points to server
        3. Firewall blocked renewal

        **Solutions:**
        ```bash
        # Manually renew certificate
        # Delete old certificate
        # Use delete-certificate-tool

        # Obtain new certificate
        # Use obtain-lets-encrypt-certificate-tool

        # Verify cron is running
        sudo crontab -l -u forge

        # Check renewal logs
        sudo tail -f /var/log/nginx/error.log
        ```

        ## Database Issues

        ### Can't Connect to Database

        **Error Messages:**
        ```
        SQLSTATE[HY000] [2002] Connection refused
        ```

        **Solution:**
        ```bash
        # Check if MySQL is running
        sudo service mysql status

        # Start MySQL if stopped
        sudo service mysql start

        # Check .env database settings
        cat .env | grep DB_

        # Test connection
        mysql -u forge -p
        ```

        **Error Messages:**
        ```
        SQLSTATE[HY000] [1045] Access denied for user 'forge'@'localhost'
        ```

        **Solution:**
        ```bash
        # Wrong database credentials
        # Check .env matches Forge database settings

        # Reset database password if needed
        # Use update-database-user-tool

        # Update .env with correct password
        ```

        ### Database Disk Full

        **Error Messages:**
        ```
        Error: Disk quota exceeded
        ```

        **Solution:**
        ```bash
        # Check disk usage
        df -h

        # Find large files/tables
        du -sh /var/lib/mysql/*

        # Solutions:
        # 1. Delete old backups
        # 2. Truncate log tables
        # 3. Optimize tables
        # 4. Upgrade server disk
        ```

        ## Server Issues

        ### Server Out of Memory

        **Symptoms:**
        - Applications crashing
        - SSH connections failing
        - Very slow performance

        **Solutions:**
        ```bash
        # Check memory usage
        free -h

        # Check what's using memory
        top
        ps aux --sort=-%mem | head

        # Quick fixes:
        # 1. Restart PHP-FPM
        sudo service php8.2-fpm restart

        # 2. Restart MySQL
        sudo service mysql restart

        # 3. Clear page cache
        echo 3 > /proc/sys/vm/drop_caches

        # Long-term: Upgrade server or optimize applications
        ```

        ### Can't SSH to Server

        **Symptoms:**
        - SSH connection refused or times out
        - Previously working

        **Causes:**
        1. Server crashed/rebooted
        2. Firewall blocked port 22
        3. SSH service stopped
        4. Too many connection attempts

        **Solutions:**
        ```bash
        # Try from Forge UI terminal
        # Or reboot server using reboot-server-tool

        # Check firewall allows port 22
        # Use list-firewall-rules-tool

        # If locked out, use Forge's browser terminal
        # Or contact server provider support
        ```

        ## Performance Issues

        ### High CPU Usage

        **Symptoms:**
        - Server slow
        - CPU at 100%

        **Causes:**
        1. PHP processes consuming CPU
        2. MySQL queries
        3. Cron jobs running

        **Solutions:**
        ```bash
        # Find CPU hogs
        top

        # Check slow MySQL queries
        mysqldumpslow /var/log/mysql/slow.log

        # Optimize:
        # - Add database indexes
        # - Cache expensive operations
        # - Limit concurrent processes
        # - Use queue for heavy tasks
        ```

        ## Emergency Procedures

        ### Site Completely Down

        1. Check server status (ping/SSH)
        2. Check PHP-FPM: `sudo service php8.2-fpm status`
        3. Check Nginx: `sudo service nginx status`
        4. Check recent deployments
        5. Check server resources (CPU/memory/disk)
        6. Review error logs
        7. Consider rollback if recent deployment

        ### Database Corrupted

        1. Stop writes immediately
        2. Restore from latest backup
        3. Replay binary logs if available
        4. Check for data consistency
        5. Document what happened

        ### Security Breach Suspected

        1. Take site offline
        2. Change all passwords/keys
        3. Review access logs
        4. Scan for malware
        5. Restore from clean backup
        6. Investigate attack vector
        7. Implement fixes
        8. Monitor closely

        ## Prevention Tips

        ✅ Monitor application logs daily
        ✅ Setup alerts for errors
        ✅ Test deployments in staging first
        ✅ Keep backups current
        ✅ Document configuration changes
        ✅ Review server metrics weekly
        ✅ Update dependencies regularly
        ✅ Use version control for everything
        MD;

        return Response::text($content);
    }
}
