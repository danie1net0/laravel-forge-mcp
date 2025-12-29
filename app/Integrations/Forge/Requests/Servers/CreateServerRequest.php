<?php

namespace App\Integrations\Forge\Requests\Servers;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use App\Integrations\Forge\Data\Servers\{CreateServerData, ServerData};
use Saloon\Traits\Body\HasJsonBody;

class CreateServerRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected CreateServerData $data
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/servers';
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
