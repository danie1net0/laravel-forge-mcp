<?php

namespace App\Integrations\Forge\Requests\Servers;

use App\Integrations\Forge\Data\Servers\ServerData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetServerRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}";
    }

    public function createDtoFromResponse(Response $response): ServerData
    {
        return ServerData::from($response->json('server'));
    }
}
