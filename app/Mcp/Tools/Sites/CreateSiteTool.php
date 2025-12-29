<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Sites;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Sites\CreateSiteData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateSiteTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
    Create a new site on a Laravel Forge server.

    This tool creates a new website/application on the specified server. The site will be
    configured with Nginx, PHP, and optionally a database and process isolation.

    **WARNING**: This is a destructive operation that creates new infrastructure on your server.

    **Required Parameters:**
    - `server_id`: The unique ID of the Forge server
    - `domain`: The domain name for the site (e.g., "example.com")
    - `project_type`: Type of project - "php" for PHP/Laravel/Symfony or "html" for static/Nuxt.js/Next.js

    **Optional Parameters:**
    - `aliases`: Array of additional domain aliases (e.g., ["www.example.com", "blog.example.com"])
    - `directory`: Custom web directory path (default: "/public" for Laravel, "/" for others)
    - `isolated`: Enable process isolation for better security (recommended for production)
    - `username`: Custom Unix username for the site
    - `database`: Database name to create and associate with the site
    - `php_version`: PHP version (php81, php82, php83, php84). Defaults to server default.
    - `nginx_template`: ID of custom Nginx template to use

    **Examples:**
    - Basic Laravel site: `{"server_id": 1, "domain": "myapp.com", "project_type": "php"}`
    - With database: `{"server_id": 1, "domain": "myapp.com", "project_type": "php", "database": "myapp_db"}`
    - Static site: `{"server_id": 1, "domain": "blog.com", "project_type": "html", "directory": "/"}`
    - Isolated site: `{"server_id": 1, "domain": "secure.com", "project_type": "php", "isolated": true}`

    The operation may take a few minutes to complete as Forge provisions the site infrastructure.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'domain' => ['required', 'string', 'min:1'],
            'project_type' => ['required', 'string', 'in:php,html'],
            'aliases' => ['sometimes', 'array'],
            'aliases.*' => ['string'],
            'directory' => ['sometimes', 'string'],
            'isolated' => ['sometimes', 'boolean'],
            'username' => ['sometimes', 'string', 'regex:/^[a-z_][a-z0-9_-]{0,31}$/'],
            'database' => ['sometimes', 'string'],
            'php_version' => ['sometimes', 'string', 'in:php81,php82,php83,php84'],
            'nginx_template' => ['sometimes', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        $data = [
            'domain' => $request->string('domain'),
            'project_type' => $request->string('project_type'),
        ];

        if ($request->has('aliases')) {
            $data['aliases'] = $request->array('aliases');
        }

        if ($request->has('directory')) {
            $data['directory'] = $request->string('directory');
        }

        if ($request->has('isolated')) {
            $data['isolated'] = $request->boolean('isolated');
        }

        if ($request->has('username')) {
            $data['username'] = $request->string('username');
        }

        if ($request->has('database')) {
            $data['database'] = $request->string('database');
        }

        if ($request->has('php_version')) {
            $data['php_version'] = $request->string('php_version');
        }

        if ($request->has('nginx_template')) {
            $data['nginx_template'] = $request->integer('nginx_template');
        }

        try {
            $createData = CreateSiteData::from($data);
            $site = $client->sites()->create($serverId, $createData);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site' => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'directory' => $site->directory,
                    'status' => $site->status,
                    'project_type' => $site->projectType,
                    'php_version' => $site->phpVersion,
                    'app_status' => $site->appStatus,
                    'is_secured' => $site->isSecured,
                    'deployment_url' => $site->deploymentUrl,
                    'created_at' => $site->createdAt,
                ],
                'message' => "Site '{$site->name}' created successfully on server #{$serverId}",
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to create site. Please check if the domain is valid and not already in use.',
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
            'domain' => $schema->string()
                ->description('The domain name for the site (e.g., "example.com")')
                ->required(),
            'project_type' => $schema->string()
                ->enum(['php', 'html'])
                ->description('Type of project: "php" for PHP/Laravel/Symfony or "html" for static/Nuxt.js/Next.js')
                ->required(),
            'aliases' => $schema->array()
                ->description('Optional: Array of additional domain aliases')
                ->items($schema->string()),
            'directory' => $schema->string()
                ->description('Optional: Custom web directory path (default: "/public" for Laravel)'),
            'isolated' => $schema->boolean()
                ->description('Optional: Enable process isolation for better security'),
            'username' => $schema->string()
                ->description('Optional: Custom Unix username for the site (lowercase, alphanumeric, max 32 chars)'),
            'database' => $schema->string()
                ->description('Optional: Database name to create and associate with the site'),
            'php_version' => $schema->string()
                ->enum(['php81', 'php82', 'php83', 'php84'])
                ->description('Optional: PHP version to use (defaults to server default)'),
            'nginx_template' => $schema->integer()
                ->description('Optional: ID of custom Nginx template to use')
                ->min(1),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
