<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Sites;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class InstallWordPressTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Install WordPress on a Laravel Forge site.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `database`: The name of the database to use
        - `user`: The database username

        **Optional Parameters:**
        - `password`: The database password (auto-generated if not provided)

        The site will be configured with WordPress and the database connection.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'database' => ['required', 'string'],
            'user' => ['required', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $database = $request->string('database')->value();
        $user = $request->string('user')->value();
        $password = $request->string('password')->value() ?: null;

        try {
            $client->sites()->installWordPress($serverId, $siteId, $database, $user, $password);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'WordPress installed successfully.',
                'server_id' => $serverId,
                'site_id' => $siteId,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
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
            'database' => $schema->string()
                ->description('The name of the database to use')
                ->required(),
            'user' => $schema->string()
                ->description('The database username')
                ->required(),
            'password' => $schema->string()
                ->description('The database password (auto-generated if not provided)'),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
