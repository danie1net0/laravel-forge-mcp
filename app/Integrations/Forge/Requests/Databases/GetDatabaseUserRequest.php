<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Databases;

use App\Integrations\Forge\Data\Databases\DatabaseUserData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetDatabaseUserRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected int $userId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/database-users/{$this->userId}";
    }

    public function createDtoFromResponse(Response $response): DatabaseUserData
    {
        return DatabaseUserData::from(array_merge($response->json('user'), ['server_id' => $this->serverId]));
    }
}
