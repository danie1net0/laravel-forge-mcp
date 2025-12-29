<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Backups;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteBackupRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $serverId,
        private readonly int $backupConfigId,
        private readonly int $backupId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/backup-configurations/{$this->backupConfigId}/backups/{$this->backupId}";
    }
}
