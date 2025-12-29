<?php

declare(strict_types=1);

namespace App\Mcp\Tools\SSHKeys;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class GetSSHKeyTool extends Tool
{
    protected string $description = 'Get detailed information about a specific SSH key.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'key_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $key = $client->sshKeys()->get($request->integer('server_id'), $request->integer('key_id'));

            return Response::text(json_encode([
                'success' => true,
                'key' => [
                    'id' => $key->id,
                    'name' => $key->name,
                    'status' => $key->status,
                    'created_at' => $key->createdAt,
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()->min(1)->required(),
            'key_id' => $schema->integer()->min(1)->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
