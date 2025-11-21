<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Servers;

use App\Services\ForgeService;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Attributes\IsDestructive;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

#[IsDestructive]
class RebootServerTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Reboot a Laravel Forge server.

        **WARNING**: This is a destructive operation that will restart the server.
        All connections will be dropped and sites will be temporarily unavailable.

        Use this only when necessary, such as after kernel updates or to resolve
        system-level issues.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server to reboot

        The server reboot typically takes 2-5 minutes to complete.
    MARKDOWN;

    public function handle(Request $request, ForgeService $forge): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');

        try {
            $forge->rebootServer($serverId);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Server reboot initiated successfully.',
                'server_id' => $serverId,
                'warning' => 'The server will be unavailable for 2-5 minutes.',
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
                ->description('The unique ID of the Forge server to reboot')
                ->minimum(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
