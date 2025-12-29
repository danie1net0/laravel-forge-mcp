<?php

declare(strict_types=1);

namespace App\Mcp\Tools\SSHKeys;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use App\Integrations\Forge\Data\SSHKeys\SSHKeyData;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class ListSSHKeysTool extends Tool
{
    protected string $description = 'List all SSH keys installed on a Forge server.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate(['server_id' => ['required', 'integer', 'min:1']]);

        try {
            $keys = $client->sshKeys()->list($request->integer('server_id'))->keys;

            $formatted = array_map(fn (SSHKeyData $key): array => [
                'id' => $key->id,
                'name' => $key->name,
                'status' => $key->status,
                'created_at' => $key->createdAt,
            ], $keys);

            return Response::text(json_encode([
                'success' => true,
                'keys' => $formatted,
                'count' => count($formatted),
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return ['server_id' => $schema->integer()->min(1)->required()];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
