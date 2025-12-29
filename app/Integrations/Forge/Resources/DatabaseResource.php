<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Databases\{CreateDatabaseData, DatabaseCollectionData, DatabaseData};
use App\Integrations\Forge\Requests\Databases\{CreateDatabaseRequest, DeleteDatabaseRequest, GetDatabaseRequest, ListDatabasesRequest, SyncDatabaseRequest};

class DatabaseResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId): DatabaseCollectionData
    {
        $request = new ListDatabasesRequest($serverId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $databaseId): DatabaseData
    {
        $request = new GetDatabaseRequest($serverId, $databaseId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(int $serverId, CreateDatabaseData $data): DatabaseData
    {
        $request = new CreateDatabaseRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, int $databaseId): void
    {
        $this->connector->send(new DeleteDatabaseRequest($serverId, $databaseId));
    }

    public function sync(int $serverId): void
    {
        $this->connector->send(new SyncDatabaseRequest($serverId));
    }
}
