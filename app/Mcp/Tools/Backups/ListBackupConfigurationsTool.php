<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Backups;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use App\Integrations\Forge\Data\Backups\BackupConfigurationData;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class ListBackupConfigurationsTool extends Tool
{
    protected string $description = 'List all backup configurations for a server.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate(['server_id' => ['required', 'integer', 'min:1']]);

        try {
            $backups = $client->backups()->listConfigurations($request->integer('server_id'))->backups;

            $formatted = array_map(fn (BackupConfigurationData $b): array => [
                'id' => $b->id,
                'provider' => $b->provider,
            ], $backups);

            return Response::text(json_encode([
                'success' => true,
                'backup_configurations' => $formatted,
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
