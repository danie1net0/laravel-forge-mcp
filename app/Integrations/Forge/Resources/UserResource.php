<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\Data\User\UserData;
use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Requests\User\GetUserRequest;

class UserResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function get(): UserData
    {
        $request = new GetUserRequest();
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }
}
