<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Backups;

use App\Integrations\Forge\Data\Backups\{BackupConfigurationData, UpdateBackupConfigurationData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class UpdateBackupConfigurationRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        private readonly int $serverId,
        private readonly int $backupId,
        private readonly UpdateBackupConfigurationData $data,
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

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return array_filter(
            $this->data->toArray(),
            fn (mixed $value): bool => $value !== null
        );
    }
}
