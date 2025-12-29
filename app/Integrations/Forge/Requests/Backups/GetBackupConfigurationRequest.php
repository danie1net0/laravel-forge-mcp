<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Backups;

use App\Integrations\Forge\Data\Backups\BackupConfigurationData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetBackupConfigurationRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly int $backupId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/backup-configurations/{$this->backupId}";
    }

    public function createDtoFromResponse(Response $response): BackupConfigurationData
    {
        return BackupConfigurationData::from(array_merge($response->json('backup'), ['server_id' => $this->serverId]));
    }
}
