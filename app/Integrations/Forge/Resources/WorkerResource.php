<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Workers\{CreateWorkerData, WorkerCollectionData, WorkerData};
use App\Integrations\Forge\Requests\Workers\{CreateWorkerRequest, DeleteWorkerRequest, GetWorkerOutputRequest, GetWorkerRequest, ListWorkersRequest, RestartWorkerRequest};

class WorkerResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId, int $siteId): WorkerCollectionData
    {
        $request = new ListWorkersRequest($serverId, $siteId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $siteId, int $workerId): WorkerData
    {
        $request = new GetWorkerRequest($serverId, $siteId, $workerId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(int $serverId, int $siteId, CreateWorkerData $data): WorkerData
    {
        $request = new CreateWorkerRequest($serverId, $siteId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function restart(int $serverId, int $siteId, int $workerId): void
    {
        $this->connector->send(new RestartWorkerRequest($serverId, $siteId, $workerId));
    }

    public function delete(int $serverId, int $siteId, int $workerId): void
    {
        $this->connector->send(new DeleteWorkerRequest($serverId, $siteId, $workerId));
    }

    public function getOutput(int $serverId, int $siteId, int $workerId): string
    {
        $response = $this->connector->send(new GetWorkerOutputRequest($serverId, $siteId, $workerId));

        return $response->json('output') ?? '';
    }
}
