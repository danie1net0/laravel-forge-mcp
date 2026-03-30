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
        private readonly ?string $cursor = null,
        private readonly int $pageSize = 30,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/database/backups";
    }

    public function createDtoFromResponse(Response $response): BackupConfigurationCollectionData
    {
        $backups = array_map(
            fn (array $backup): array => array_merge($backup, ['server_id' => $this->serverId]),
            $response->json('backups')
        );

        return BackupConfigurationCollectionData::from(['backups' => $backups]);
    }

    /**
     * @return array<string, string|int>
     */
    protected function defaultQuery(): array
    {
        $query = ['page[size]' => $this->pageSize];

        if ($this->cursor !== null) {
            $query['page[cursor]'] = $this->cursor;
        }

        return $query;
    }
}
