<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Commands;

use App\Integrations\Forge\Data\Sites\{ExecuteSiteCommandData, SiteCommandData};
use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class ExecuteSiteCommandTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Execute a command on a Laravel Forge site via SSH.

        This tool allows you to run arbitrary shell commands on your Forge site server.
        The command will be executed in the context of the site's directory with the
        appropriate user permissions.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `command`: The shell command to execute

        **Common Use Cases:**
        - Run artisan commands: `php artisan cache:clear`
        - Install dependencies: `composer install --no-dev`
        - Run database migrations: `php artisan migrate --force`
        - Clear application cache: `php artisan config:cache`
        - Build frontend assets: `npm run build`
        - Check disk usage: `df -h`
        - View logs: `tail -n 100 storage/logs/laravel.log`

        **Important Notes:**
        - Commands are executed asynchronously
        - The tool returns immediately with a command ID
        - Use get-site-command-tool to retrieve command output
        - Commands timeout after a default period (usually 60 seconds)
        - Be careful with destructive commands (rm, truncate, etc.)

        **Security Warning:**
        - Only execute trusted commands
        - Avoid exposing sensitive data in command output
        - Be cautious with commands that modify production data

        Returns the command ID and initial status. Use the command ID with
        get-site-command-tool to retrieve the command output.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'command' => ['required', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $command = $request->string('command')->value();

        try {
            $commandData = ExecuteSiteCommandData::from(['command' => $command]);
            $commandArray = $client->sites()->executeCommand($serverId, $siteId, $commandData);
            $siteCommand = SiteCommandData::from($commandArray);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Command queued for execution. Use get-site-command-tool to retrieve output.',
                'command' => [
                    'id' => $siteCommand->id,
                    'command' => $siteCommand->command,
                    'status' => $siteCommand->status,
                    'created_at' => $siteCommand->createdAt,
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to execute command. Please check the parameters and try again.',
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
            'command' => $schema->string()
                ->description('The shell command to execute (e.g., "php artisan cache:clear")')
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
