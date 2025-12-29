<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Credentials\CredentialCollectionData;
use App\Integrations\Forge\Requests\Credentials\ListCredentialsRequest;

class CredentialResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(): CredentialCollectionData
    {
        $request = new ListCredentialsRequest();
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }
}
