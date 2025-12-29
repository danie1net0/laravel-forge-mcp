<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Servers;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class GetServerLogTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get a server log file content.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server

        **Optional Parameters:**
        - `file`: The log file to retrieve (default: 'auth')
            Available options: 'auth', 'daemon', 'syslog', 'kern', 'mail', 'dpkg', 'apt'

        Returns the content of the requested log file.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'file' => ['nullable', 'string', 'in:auth,daemon,syslog,kern,mail,dpkg,apt'],
        ]);

        $serverId = $request->integer('server_id');
        $file = $request->string('file')->value() ?: 'auth';

        try {
            $content = $client->servers()->getLog($serverId, $file);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'file' => $file,
                'content' => $content,
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
            'file' => $schema->string()
                ->description('The log file to retrieve')
                ->enum(['auth', 'daemon', 'syslog', 'kern', 'mail', 'dpkg', 'apt'])
                ->default('auth'),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
