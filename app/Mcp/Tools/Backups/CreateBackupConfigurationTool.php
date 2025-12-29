<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Backups;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Backups\CreateBackupConfigurationData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateBackupConfigurationTool extends Tool
{
    protected string $description = 'Create a new backup configuration.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'provider' => ['required', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $data = $request->except('server_id');

        try {
            $createData = CreateBackupConfigurationData::from($data);
            $backup = $client->backups()->createConfiguration($serverId, $createData);

            return Response::text(json_encode([
                'success' => true,
                'backup' => ['id' => $backup->id, 'provider' => $backup->provider],
                'message' => 'Backup configuration created successfully',
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()->min(1)->required(),
            'provider' => $schema->string()->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
