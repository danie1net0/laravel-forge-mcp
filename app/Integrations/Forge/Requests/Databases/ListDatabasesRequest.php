<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Databases;

use App\Integrations\Forge\Data\Databases\DatabaseCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListDatabasesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        private readonly ?string $cursor = null,
        private readonly int $pageSize = 30,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/database/schemas";
    }

    public function createDtoFromResponse(Response $response): DatabaseCollectionData
    {
        $databases = $response->json('databases', []);

        $databasesWithServerId = array_map(
            fn (array $database) => array_merge($database, ['server_id' => $this->serverId]),
            $databases
        );

        return DatabaseCollectionData::from([
            'databases' => $databasesWithServerId,
        ]);
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
