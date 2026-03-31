<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Servers\{CreateServerData, ServerCollectionData, ServerData};
use App\Integrations\Forge\Requests\Servers\{
    CreateServerRequest,
    DeleteServerRequest,
    GetEventOutputRequest,
    GetServerRequest,
    ListEventsRequest,
    ListServersRequest,
    PowerCycleServerRequest,
    RebootServerRequest,
    UpdateDatabasePasswordRequest
};

class ServerResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(?string $cursor = null, int $pageSize = 30): ServerCollectionData
    {
        $request = new ListServersRequest($cursor, $pageSize);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId): ServerData
    {
        $request = new GetServerRequest($serverId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(CreateServerData $data): ServerData
    {
        $request = new CreateServerRequest($data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, bool $preserveAtProvider = false): void
    {
        $this->connector->send(new DeleteServerRequest($serverId, $preserveAtProvider));
    }

    public function reboot(int $serverId): void
    {
        $this->connector->send(new RebootServerRequest($serverId));
    }

    public function powerCycle(int $serverId): void
    {
        $this->connector->send(new PowerCycleServerRequest($serverId));
    }

    public function updateDatabasePassword(int $serverId, ?string $password = null): void
    {
        $this->connector->send(new UpdateDatabasePasswordRequest($serverId, $password));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listEvents(int $serverId, ?string $cursor = null, int $pageSize = 30): array
    {
        $response = $this->connector->send(new ListEventsRequest($serverId, $cursor, $pageSize));

        return $response->json('events') ?? [];
    }

    public function getEventOutput(int $serverId, int $eventId): string
    {
        $response = $this->connector->send(new GetEventOutputRequest($serverId, $eventId));

        return $response->json('output') ?? '';
    }
}
