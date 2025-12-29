<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Servers\{CreateServerData, ServerCollectionData, ServerData, UpdateServerData};
use App\Integrations\Forge\Requests\Servers\{
    CreateServerRequest,
    DeleteServerRequest,
    GetEventOutputRequest,
    GetServerLogRequest,
    GetServerRequest,
    ListEventsRequest,
    ListServersRequest,
    ReactivateServerRequest,
    RebootServerRequest,
    ReconnectServerRequest,
    RevokeServerAccessRequest,
    UpdateDatabasePasswordRequest,
    UpdateServerRequest
};

class ServerResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(): ServerCollectionData
    {
        $request = new ListServersRequest();
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

    public function update(int $serverId, UpdateServerData $data): ServerData
    {
        $request = new UpdateServerRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId): void
    {
        $this->connector->send(new DeleteServerRequest($serverId));
    }

    public function reboot(int $serverId): void
    {
        $this->connector->send(new RebootServerRequest($serverId));
    }

    public function updateDatabasePassword(int $serverId): void
    {
        $this->connector->send(new UpdateDatabasePasswordRequest($serverId));
    }

    public function revokeAccess(int $serverId): void
    {
        $this->connector->send(new RevokeServerAccessRequest($serverId));
    }

    public function reconnect(int $serverId): string
    {
        $response = $this->connector->send(new ReconnectServerRequest($serverId));

        return $response->json('public_key') ?? '';
    }

    public function reactivate(int $serverId): void
    {
        $this->connector->send(new ReactivateServerRequest($serverId));
    }

    public function getLog(int $serverId, string $file = 'auth'): string
    {
        $response = $this->connector->send(new GetServerLogRequest($serverId, $file));

        return $response->json('content') ?? '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listEvents(int $serverId): array
    {
        $response = $this->connector->send(new ListEventsRequest($serverId));

        return $response->json('events') ?? [];
    }

    public function getEventOutput(int $serverId, int $eventId): string
    {
        $response = $this->connector->send(new GetEventOutputRequest($serverId, $eventId));

        return $response->json('output') ?? '';
    }
}
