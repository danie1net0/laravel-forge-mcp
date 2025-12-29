<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\User;

use App\Integrations\Forge\Data\User\UserData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetUserRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/user';
    }

    public function createDtoFromResponse(Response $response): UserData
    {
        return UserData::from($response->json('user'));
    }
}
