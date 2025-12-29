<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Databases;

use App\Integrations\Forge\Data\Databases\DatabaseUserCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListDatabaseUsersRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/database-users";
    }

    public function createDtoFromResponse(Response $response): DatabaseUserCollectionData
    {
        $users = $response->json('users', []);

        $usersWithServerId = array_map(
            fn (array $user) => array_merge($user, ['server_id' => $this->serverId]),
            $users
        );

        return DatabaseUserCollectionData::from([
            'users' => $usersWithServerId,
        ]);
    }
}
