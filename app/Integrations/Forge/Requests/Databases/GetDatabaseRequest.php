<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Databases;

use App\Integrations\Forge\Data\Databases\DatabaseData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetDatabaseRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected int $databaseId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/databases/{$this->databaseId}";
    }

    public function createDtoFromResponse(Response $response): DatabaseData
    {
        return DatabaseData::from(array_merge($response->json('database'), ['server_id' => $this->serverId]));
    }
}
