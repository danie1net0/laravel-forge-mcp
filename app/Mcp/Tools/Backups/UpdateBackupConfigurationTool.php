<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Backups;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Backups\UpdateBackupConfigurationData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class UpdateBackupConfigurationTool extends Tool
{
    protected string $description = 'Update an existing backup configuration.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'backup_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $backupId = $request->integer('backup_id');
        $data = $request->except(['server_id', 'backup_id']);

        try {
            $updateData = UpdateBackupConfigurationData::from($data);
            $backup = $client->backups()->updateConfiguration($serverId, $backupId, $updateData);

            return Response::text(json_encode([
                'success' => true,
                'backup' => ['id' => $backup->id],
                'message' => 'Backup configuration updated successfully',
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()->min(1)->required(),
            'backup_id' => $schema->integer()->min(1)->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
