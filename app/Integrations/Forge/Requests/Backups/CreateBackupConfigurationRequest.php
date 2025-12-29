<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Backups;

use App\Integrations\Forge\Data\Backups\{BackupConfigurationData, CreateBackupConfigurationData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class CreateBackupConfigurationRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $serverId,
        private readonly CreateBackupConfigurationData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/backup-configurations";
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
