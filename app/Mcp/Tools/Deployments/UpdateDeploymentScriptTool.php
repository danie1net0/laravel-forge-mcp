<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Deployments;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class UpdateDeploymentScriptTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
    Update the deployment script for a site.

    The deployment script is a bash script that runs when a deployment is triggered.
    You can customize it to include any commands needed for your deployment process.

    **Common deployment script commands:**
    - `cd $FORGE_SITE_PATH` - Change to site directory
    - `git pull origin $FORGE_SITE_BRANCH` - Pull latest code
    - `composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader`
    - `npm ci && npm run build` - Install and build frontend assets
    - `php artisan migrate --force` - Run database migrations
    - `php artisan config:cache` - Cache configuration
    - `php artisan route:cache` - Cache routes
    - `php artisan view:cache` - Cache views
    - `php artisan queue:restart` - Restart queue workers

    **Required Parameters:**
    - `server_id`: The unique ID of the Forge server
    - `site_id`: The unique ID of the site
    - `content`: The new deployment script content (bash script)

    **Example:**
    ```json
    {
        "server_id": 1,
        "site_id": 1,
        "content": "cd $FORGE_SITE_PATH git pull origin $FORGE_SITE_BRANCH composer install --no-interaction --prefer-dist --optimize-autoloader php artisan migrate --force php artisan config:cache php artisan queue:restart"
    }
    ```

    **Available Environment Variables:**
    - `$FORGE_SITE_PATH` - Full path to the site directory
    - `$FORGE_SITE_BRANCH` - The Git branch being deployed
    - `$FORGE_PHP` - Path to the PHP binary
    - `$FORGE_COMPOSER` - Path to Composer
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'content' => ['required', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $content = $request->string('content')->value();

        try {
            $client->sites()->updateDeploymentScript($serverId, $siteId, $content);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'message' => 'Deployment script updated successfully',
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to update deployment script.',
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
            'content' => $schema->string()
                ->description('The new deployment script content (bash script)')
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
