<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Backups;

use App\Integrations\Forge\Data\Backups\BackupConfigurationCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListBackupConfigurationsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/backup-configurations";
    }

    public function createDtoFromResponse(Response $response): BackupConfigurationCollectionData
    {
        $backups = array_map(
            fn (array $backup): array => array_merge($backup, ['server_id' => $this->serverId]),
            $response->json('backups')
        );

        return BackupConfigurationCollectionData::from(['backups' => $backups]);
    }
}
