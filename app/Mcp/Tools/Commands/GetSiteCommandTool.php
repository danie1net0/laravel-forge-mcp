<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Commands;

use App\Integrations\Forge\Data\Sites\SiteCommandData;
use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class GetSiteCommandTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get detailed information about a specific command executed on a site.

        Returns complete command information including:
        - Command ID and text
        - Execution status (pending, running, finished, failed)
        - Command output (stdout and stderr)
        - Exit code
        - Execution duration
        - User who executed the command
        - Timestamps (created, started, finished)

        This is useful for:
        - Debugging command failures
        - Viewing command output
        - Monitoring long-running commands
        - Auditing specific operations

        This is a read-only operation and will not modify any data.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `command_id`: The unique ID of the command
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'command_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $commandId = $request->integer('command_id');

        try {
            $commandArray = $client->sites()->getCommand($serverId, $siteId, $commandId);
            $command = SiteCommandData::from($commandArray);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'command' => $command->toArray(),
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve command details. The command may not exist.',
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
            'command_id' => $schema->integer()
                ->description('The unique ID of the command')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
