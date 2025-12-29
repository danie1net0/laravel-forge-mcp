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
class ListCommandHistoryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List the history of commands executed on a specific site.

        Returns a list of all commands that have been executed on the site, including:
        - Command ID
        - Command text
        - Execution status (pending, running, finished, failed)
        - User who executed the command
        - Execution time and duration
        - Created timestamp

        This is useful for:
        - Monitoring custom command executions
        - Debugging failed commands
        - Auditing site operations
        - Tracking maintenance tasks

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
            $commandsArray = $client->sites()->commandHistory($serverId, $siteId);
            $commands = array_map(fn (array $command): array => SiteCommandData::from($command)->toArray(), $commandsArray);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'commands' => $commands,
                'count' => count($commands),
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve command history. Please check if the site exists.',
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
