<?php

namespace App\Integrations\Forge\Requests\Servers;

use App\Integrations\Forge\Data\Servers\ServerCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListServersRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/servers';
    }

    public function createDtoFromResponse(Response $response): ServerCollectionData
    {
        return ServerCollectionData::from($response->json());
    }
}
