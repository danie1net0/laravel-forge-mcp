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
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/databases";
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
}
