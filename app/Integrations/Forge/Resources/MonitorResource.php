<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Monitors\{CreateMonitorData, MonitorCollectionData, MonitorData};
use App\Integrations\Forge\Requests\Monitors\{CreateMonitorRequest, DeleteMonitorRequest, GetMonitorRequest, ListMonitorsRequest};

class MonitorResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId): MonitorCollectionData
    {
        $request = new ListMonitorsRequest($serverId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $monitorId): MonitorData
    {
        $request = new GetMonitorRequest($serverId, $monitorId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(int $serverId, CreateMonitorData $data): MonitorData
    {
        $request = new CreateMonitorRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, int $monitorId): void
    {
        $this->connector->send(new DeleteMonitorRequest($serverId, $monitorId));
    }
}
