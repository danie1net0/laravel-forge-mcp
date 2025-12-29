<?php

declare(strict_types=1);

namespace App\Mcp\Tools\SSHKeys;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\SSHKeys\CreateSSHKeyData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateSSHKeyTool extends Tool
{
    protected string $description = 'Add a new SSH key to a Forge server.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string'],
            'key' => ['required', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $data = $request->except('server_id');

        try {
            $keyData = CreateSSHKeyData::from($data);
            $key = $client->sshKeys()->create($serverId, $keyData);

            return Response::text(json_encode([
                'success' => true,
                'key' => ['id' => $key->id, 'name' => $key->name, 'status' => $key->status],
                'message' => 'SSH key created successfully',
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()->min(1)->required(),
            'name' => $schema->string()->required(),
            'key' => $schema->string()->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
