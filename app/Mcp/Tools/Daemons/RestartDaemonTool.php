<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Daemons;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class RestartDaemonTool extends Tool
{
    protected string $description = 'Restart a daemon process. Requires server_id and daemon_id.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate(['server_id' => ['required', 'integer', 'min:1'], 'daemon_id' => ['required', 'integer', 'min:1']]);

        try {
            $client->daemons()->restart($request->integer('server_id'), $request->integer('daemon_id'));

            return Response::text(json_encode(['success' => true, 'message' => 'Daemon restarted'], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return ['server_id' => $schema->integer()->min(1)->required(), 'daemon_id' => $schema->integer()->min(1)->required()];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
