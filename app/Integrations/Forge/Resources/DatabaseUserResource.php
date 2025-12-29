<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Databases\{CreateDatabaseUserData, DatabaseUserCollectionData, DatabaseUserData, UpdateDatabaseUserData};
use App\Integrations\Forge\Requests\Databases\{CreateDatabaseUserRequest, DeleteDatabaseUserRequest, GetDatabaseUserRequest, ListDatabaseUsersRequest, UpdateDatabaseUserRequest};

class DatabaseUserResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId): DatabaseUserCollectionData
    {
        $request = new ListDatabaseUsersRequest($serverId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $userId): DatabaseUserData
    {
        $request = new GetDatabaseUserRequest($serverId, $userId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(int $serverId, CreateDatabaseUserData $data): DatabaseUserData
    {
        $request = new CreateDatabaseUserRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function update(int $serverId, int $userId, UpdateDatabaseUserData $data): DatabaseUserData
    {
        $request = new UpdateDatabaseUserRequest($serverId, $userId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, int $userId): void
    {
        $this->connector->send(new DeleteDatabaseUserRequest($serverId, $userId));
    }
}
