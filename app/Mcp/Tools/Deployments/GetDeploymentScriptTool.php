<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Deployments;

use App\Integrations\Forge\ForgeClient;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};
use Laravel\Mcp\Server\Tool;
use Exception;

#[IsReadOnly, IsIdempotent]
class GetDeploymentScriptTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get the deployment script for a specific site.

        **Common Use Cases:**
        - Reviewing deployment script before first deployment
        - Verifying script includes necessary commands (migrations, cache clearing, etc.)
        - Troubleshooting deployment failures
        - Copying script to use as template for other sites
        - Auditing what happens during deployments

        **What You'll Get:**
        Returns the bash script that runs every time a deployment is triggered. This typically includes:
        - Change to site directory (`cd $FORGE_SITE_PATH`)
        - Git pull commands (`git pull origin $FORGE_SITE_BRANCH`)
        - Composer install (production dependencies)
        - NPM/Yarn commands (build frontend assets)
        - Artisan commands (migrate, cache:clear, queue:restart, etc.)
        - Custom commands specific to your application
        - PHP-FPM reload

        **Standard Laravel Deployment Script:**
        ```bash
        cd $FORGE_SITE_PATH
        git pull origin $FORGE_SITE_BRANCH

        $FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

        ( flock -w 10 9 || exit 1
            echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

        if [ -f artisan ]; then
            $FORGE_PHP artisan migrate --force
            $FORGE_PHP artisan config:cache
            $FORGE_PHP artisan route:cache
            $FORGE_PHP artisan view:cache
            $FORGE_PHP artisan queue:restart
        fi
        ```

        **What to Look For:**
        ✅ `--no-interaction` flags (prevents hanging on prompts)
        ✅ `artisan migrate --force` (runs migrations in production)
        ✅ `queue:restart` (ensures workers load new code)
        ✅ Cache commands (config:cache, route:cache, view:cache)
        ⚠️ Missing `npm run build` (if you have frontend assets)
        ⚠️ Missing `composer install` flags (could cause issues)
        ⚠️ Custom commands without error handling

        **Common Optimizations:**
        - Add `npm ci && npm run build` for frontend builds
        - Add `php artisan storage:link` for first deployment
        - Add `php artisan horizon:terminate` if using Horizon
        - Add `php artisan optimize` for performance
        - Add maintenance mode for zero-downtime: `php artisan down` before, `php artisan up` after

        **Available Environment Variables:**
        - `$FORGE_SITE_PATH` - Full path to site directory
        - `$FORGE_SITE_BRANCH` - Git branch being deployed
        - `$FORGE_PHP` - Path to PHP binary
        - `$FORGE_COMPOSER` - Path to Composer
        - `$FORGE_PHP_FPM` - PHP-FPM service name

        **When to Review:**
        - Before first deployment to a new site
        - After changing application requirements (new dependencies, etc.)
        - When deployments are failing or hanging
        - When you need to add custom deployment steps
        - After major framework updates

        **Next Steps:**
        - Update script: `update-deployment-script-tool`
        - Deploy with this script: `deploy-site-tool`
        - Check deployment logs: `get-deployment-log-tool`

        This is a read-only operation and will not modify any data.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');

        try {
            $script = $client->sites()->deploymentScript($serverId, $siteId);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'script' => $script,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve deployment script.',
            ], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()
                ->description('The unique ID of the Forge server')
                ->min(1)
                ->required(),
            'site_id' => $schema->integer()
                ->description('The unique ID of the site')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
