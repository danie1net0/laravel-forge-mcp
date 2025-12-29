<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Credentials;

use App\Integrations\Forge\Data\Credentials\CredentialCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListCredentialsRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/credentials';
    }

    public function createDtoFromResponse(Response $response): CredentialCollectionData
    {
        return CredentialCollectionData::fromResponse($response->json());
    }
}
