<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Resource;

class PHPUpgradeGuideResource extends Resource
{
    protected string $uri = 'forge://guides/php-upgrade';

    protected string $name = 'PHP Version Upgrade Guide';

    protected string $description = 'Step-by-step guide for upgrading PHP versions on Forge servers with zero downtime';

    protected string $mimeType = 'text/markdown';

    public function handle(Request $request): Response
    {
        $content = <<<'MD'
        # PHP Version Upgrade Guide for Laravel Forge

        ## Overview

        Upgrading PHP is essential for security, performance, and accessing new features.
        This guide covers safe upgrade procedures with minimal downtime.

        ## Pre-Upgrade Checklist

        ### 1. Check Compatibility

        **Laravel Version Requirements:**
        - Laravel 11.x: PHP 8.2+
        - Laravel 10.x: PHP 8.1+
        - Laravel 9.x: PHP 8.0+
        - Laravel 8.x: PHP 7.3+

        **Check your dependencies:**
        ```bash
        composer outdated
        composer why-not php 8.4
        ```

        ### 2. Review Breaking Changes

        **PHP 8.4 Breaking Changes:**
        - Implicit nullable types deprecated
        - `$GLOBALS` is now read-only
        - New DOM extension API

        **PHP 8.3 Breaking Changes:**
        - More restrictive date/time parsing
        - Unserialize warnings
        - FFI changes

        **PHP 8.2 Breaking Changes:**
        - Dynamic properties deprecated
        - Some string functions require explicit encoding
        - Partial support for null in internal functions

        ### 3. Test Locally First

        Before upgrading Forge servers:
        1. Update local PHP to target version
        2. Run full test suite: `php artisan test`
        3. Test all critical functionality manually
        4. Check for deprecation notices in logs

        ## Upgrade Procedure

        ### Method 1: Change PHP Version in Forge (Recommended)

        **For individual sites:**

        1. Go to Forge → Server → Sites → [Your Site]
        2. Click "PHP Version" dropdown
        3. Select new version (e.g., PHP 8.4)
        4. Click "Change"
        5. Forge will update Nginx config and restart services

        **Using MCP Tools:**

        ```
        1. Use `get-site-tool` to see current PHP version
        2. Use `change-php-version-tool` with:
           - server_id: your server
           - site_id: your site
           - php_version: php84
        ```

        ### Method 2: Install New PHP Version on Server

        If the PHP version isn't available:

        1. Use `execute-site-command-tool` or SSH:
           ```bash
           sudo apt update
           sudo add-apt-repository ppa:ondrej/php
           sudo apt install php8.4-fpm php8.4-cli php8.4-mysql php8.4-xml \
               php8.4-mbstring php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath \
               php8.4-redis php8.4-intl
           ```

        2. Verify installation:
           ```bash
           php8.4 -v
           ```

        3. Change site to new version in Forge

        ### Method 3: Zero-Downtime Upgrade

        For production sites requiring zero downtime:

        1. **Prepare new server:**
           - Use `create-server-tool` with new PHP version
           - Clone site configuration

        2. **Migrate site:**
           - Use `clone-site-tool` to copy to new server
           - Sync database
           - Update DNS to new server IP

        3. **Cutover:**
           - Use load balancer for gradual switch
           - Or instant DNS change during low-traffic

        4. **Cleanup:**
           - Monitor new server
           - Decommission old server after verification

        ## Post-Upgrade Tasks

        ### 1. Clear Caches

        ```bash
        php artisan config:clear
        php artisan cache:clear
        php artisan route:clear
        php artisan view:clear
        php artisan optimize
        ```

        ### 2. Restart Services

        ```bash
        sudo service php8.4-fpm restart
        sudo service nginx reload
        ```

        Or use MCP:
        ```
        Use restart-php-tool with server_id and version php84
        ```

        ### 3. Update OPcache

        ```
        Use clear-opcache-tool to flush cached bytecode
        ```

        ### 4. Verify Functionality

        - Test login/authentication
        - Test critical business processes
        - Check queue workers are processing
        - Verify scheduled tasks run
        - Monitor error logs

        ### 5. Update Deployment Script

        Update deploy script to use new PHP binary:

        ```bash
        # Old
        php artisan migrate --force

        # New (if needed)
        /usr/bin/php8.4 artisan migrate --force
        ```

        ## Common Issues and Solutions

        ### Issue: Extensions Missing

        **Symptom:** "Class not found" errors after upgrade

        **Solution:**
        ```bash
        # List installed extensions
        php -m

        # Install missing extensions
        sudo apt install php8.4-[extension]

        # Common extensions needed:
        php8.4-mysql php8.4-pgsql php8.4-redis php8.4-gd
        php8.4-imagick php8.4-xml php8.4-zip php8.4-bcmath
        php8.4-intl php8.4-soap php8.4-mbstring
        ```

        ### Issue: Composer Dependencies Fail

        **Symptom:** Composer install fails with version constraints

        **Solution:**
        ```bash
        # Update dependencies locally first
        composer update

        # If package doesn't support new PHP:
        composer require vendor/package --ignore-platform-reqs
        # Then fix the package or find alternative
        ```

        ### Issue: Deprecation Warnings Flooding Logs

        **Symptom:** Logs filled with PHP deprecation notices

        **Solution:**
        ```php
        // In config/logging.php, filter deprecations in production
        'deprecations' => [
            'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        ],
        ```

        ### Issue: OPcache Not Working

        **Symptom:** Performance degradation after upgrade

        **Solution:**
        ```bash
        # Check OPcache is enabled
        php -i | grep opcache

        # Verify settings in php.ini
        opcache.enable=1
        opcache.memory_consumption=256
        opcache.max_accelerated_files=20000
        ```

        ### Issue: Queue Workers Not Processing

        **Symptom:** Jobs stuck in queue after upgrade

        **Solution:**
        ```bash
        # Restart queue workers
        php artisan queue:restart

        # Or via Forge - restart workers
        ```

        Using MCP:
        ```
        Use restart-worker-tool for each queue worker
        ```

        ## PHP Configuration Optimization

        ### Recommended php.ini Settings for Production

        ```ini
        ; Memory
        memory_limit = 512M

        ; Execution
        max_execution_time = 60
        max_input_time = 60

        ; Upload
        upload_max_filesize = 64M
        post_max_size = 64M

        ; OPcache
        opcache.enable = 1
        opcache.memory_consumption = 256
        opcache.max_accelerated_files = 20000
        opcache.validate_timestamps = 0  ; Disable in production
        opcache.revalidate_freq = 0

        ; JIT (PHP 8.0+)
        opcache.jit = 1255
        opcache.jit_buffer_size = 128M

        ; Errors
        display_errors = Off
        log_errors = On
        error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT

        ; Security
        expose_php = Off
        session.cookie_secure = 1
        session.cookie_httponly = 1
        ```

        ### FPM Pool Settings

        `/etc/php/8.4/fpm/pool.d/www.conf`:

        ```ini
        pm = dynamic
        pm.max_children = 50
        pm.start_servers = 10
        pm.min_spare_servers = 5
        pm.max_spare_servers = 20
        pm.max_requests = 500
        ```

        ## Rollback Procedure

        If upgrade fails:

        1. **Quick Rollback via Forge:**
           - Change site PHP version back to previous
           - Restart services

        2. **If new PHP was installed:**
           ```bash
           # Switch default PHP
           sudo update-alternatives --set php /usr/bin/php8.3

           # Update site in Forge
           ```

        3. **If using new server:**
           - Point DNS back to old server
           - Keep old server running until issue resolved

        ## Version-Specific Upgrade Notes

        ### Upgrading to PHP 8.4

        New features to leverage:
        - Property hooks
        - Asymmetric visibility
        - New array functions
        - Improved JIT performance

        ### Upgrading to PHP 8.3

        New features:
        - Typed class constants
        - `json_validate()` function
        - `Randomizer` additions
        - Read-only classes improvements

        ### Upgrading to PHP 8.2

        Key changes:
        - Readonly classes
        - Disjunctive Normal Form Types
        - Constants in traits
        - Sensitive parameter redaction

        **Dynamic properties deprecated:**
        ```php
        // Instead of:
        $object->undefinedProperty = 'value';

        // Use:
        #[AllowDynamicProperties]
        class MyClass { }
        // Or use stdClass, or define properties
        ```

        ## Monitoring After Upgrade

        ### Check Error Rates

        ```bash
        tail -f /var/log/php8.4-fpm.log
        tail -f storage/logs/laravel.log
        ```

        ### Performance Metrics

        Use Forge monitors or:
        - New Relic
        - Blackfire
        - Laravel Telescope

        ### Key Metrics to Watch

        - Response times (should improve or stay same)
        - Memory usage (may differ)
        - Error rates (should be zero or minimal)
        - Queue processing speed
        - OPcache hit rate (should be >95%)

        ## Best Practices Summary

        1. ✅ Always test locally before production
        2. ✅ Check Laravel/package compatibility first
        3. ✅ Have a rollback plan ready
        4. ✅ Upgrade during low-traffic periods
        5. ✅ Clear all caches after upgrade
        6. ✅ Monitor closely for 24-48 hours
        7. ✅ Update deployment scripts if needed
        8. ✅ Document the upgrade for your team

        ## Related Tools

        - `change-php-version-tool` - Change site PHP version
        - `get-php-version-tool` - Get current PHP version
        - `restart-php-tool` - Restart PHP-FPM
        - `clear-opcache-tool` - Clear OPcache
        - `get-server-log-tool` - Check server logs

        MD;

        return Response::text($content);
    }
}
