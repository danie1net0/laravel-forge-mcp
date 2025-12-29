<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Daemons\{CreateDaemonData, DaemonCollectionData, DaemonData};
use App\Integrations\Forge\Requests\Daemons\{CreateDaemonRequest, DeleteDaemonRequest, GetDaemonRequest, ListDaemonsRequest, RestartDaemonRequest};

class DaemonResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId): DaemonCollectionData
    {
        $request = new ListDaemonsRequest($serverId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $daemonId): DaemonData
    {
        $request = new GetDaemonRequest($serverId, $daemonId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(int $serverId, CreateDaemonData $data): DaemonData
    {
        $request = new CreateDaemonRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function restart(int $serverId, int $daemonId): void
    {
        $this->connector->send(new RestartDaemonRequest($serverId, $daemonId));
    }

    public function delete(int $serverId, int $daemonId): void
    {
        $this->connector->send(new DeleteDaemonRequest($serverId, $daemonId));
    }
}
