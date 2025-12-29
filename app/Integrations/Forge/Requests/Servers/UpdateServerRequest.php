<?php

namespace App\Integrations\Forge\Requests\Servers;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use App\Integrations\Forge\Data\Servers\{ServerData, UpdateServerData};
use Saloon\Traits\Body\HasJsonBody;

class UpdateServerRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        protected int $serverId,
        protected UpdateServerData $data
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

    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
