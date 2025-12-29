<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\SSHKeys\{CreateSSHKeyData, SSHKeyCollectionData, SSHKeyData};
use App\Integrations\Forge\Requests\SSHKeys\{CreateSSHKeyRequest, DeleteSSHKeyRequest, GetSSHKeyRequest, ListSSHKeysRequest};

class SSHKeyResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId): SSHKeyCollectionData
    {
        $request = new ListSSHKeysRequest($serverId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $keyId): SSHKeyData
    {
        $request = new GetSSHKeyRequest($serverId, $keyId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(int $serverId, CreateSSHKeyData $data): SSHKeyData
    {
        $request = new CreateSSHKeyRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, int $keyId): void
    {
        $this->connector->send(new DeleteSSHKeyRequest($serverId, $keyId));
    }
}
