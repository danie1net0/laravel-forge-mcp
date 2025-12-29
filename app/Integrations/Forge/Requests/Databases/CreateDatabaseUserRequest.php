<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Databases;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use App\Integrations\Forge\Data\Databases\{CreateDatabaseUserData, DatabaseUserData};
use Saloon\Traits\Body\HasJsonBody;

class CreateDatabaseUserRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected CreateDatabaseUserData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/database-users";
    }

    public function createDtoFromResponse(Response $response): DatabaseUserData
    {
        $user = $response->json('user');

        return DatabaseUserData::from(array_merge($user, [
            'server_id' => $this->serverId,
        ]));
    }

    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
