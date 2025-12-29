<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Daemons;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Daemons\CreateDaemonData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateDaemonTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Create a new daemon (long-running process) on a Laravel Forge server.

        Daemons are background processes that run continuously, such as queue workers,
        websocket servers, or other long-running tasks.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `command`: The command to run (e.g., "php artisan horizon" or "php artisan queue:work")
        - `directory`: The directory where the command should be executed

        **Optional Parameters:**
        - `user`: Unix user to run the daemon as (defaults to "forge")
        - `processes`: Number of processes to run (defaults to 1)
        - `startsecs`: Number of seconds the process must stay running to be considered successful (defaults to 1)

        **Examples:**
        - Laravel Horizon: `php artisan horizon`
        - Queue Worker: `php artisan queue:work --tries=3`
        - Laravel Reverb: `php artisan reverb:start`

        **Warning:** Daemons will automatically restart if they crash. Make sure your
        command is correct before creating the daemon.

        Returns the created daemon information.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'command' => ['required', 'string'],
            'directory' => ['required', 'string'],
            'user' => ['nullable', 'string', 'max:255'],
            'processes' => ['nullable', 'integer', 'min:1', 'max:10'],
            'startsecs' => ['nullable', 'integer', 'min:0'],
        ]);

        $serverId = $request->integer('server_id');
        $data = [
            'command' => $request->string('command'),
            'directory' => $request->string('directory'),
        ];

        if ($request->has('user')) {
            $data['user'] = $request->string('user');
        }

        if ($request->has('processes')) {
            $data['processes'] = $request->integer('processes');
        }

        if ($request->has('startsecs')) {
            $data['startsecs'] = $request->integer('startsecs');
        }

        try {
            $daemonData = CreateDaemonData::from($data);
            $daemon = $client->daemons()->create($serverId, $daemonData);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Daemon created successfully',
                'daemon' => [
                    'id' => $daemon->id,
                    'command' => $daemon->command,
                    'user' => $daemon->user,
                    'directory' => $daemon->directory,
                    'status' => $daemon->status,
                    'created_at' => $daemon->createdAt,
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to create daemon. Please check the parameters and try again.',
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
            'command' => $schema->string()
                ->description('The command to run (e.g., "php artisan horizon")')
                ->required(),
            'directory' => $schema->string()
                ->description('The directory where the command should be executed')
                ->required(),
            'user' => $schema->string()
                ->description('Unix user to run the daemon as (defaults to "forge")'),
            'processes' => $schema->integer()
                ->description('Number of processes to run (defaults to 1, max 10)')
                ->min(1)
                ->max(10),
            'startsecs' => $schema->integer()
                ->description('Seconds the process must stay running to be considered successful (defaults to 1)')
                ->min(0),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
