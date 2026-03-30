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

    public function update(int $serverId, UpdateServerData $data): ServerData
    {
        $request = new UpdateServerRequest($serverId, $data);
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

    public function updateDatabasePassword(int $serverId, ?string $password = null): void
    {
        $this->connector->send(new UpdateDatabasePasswordRequest($serverId, $password));
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
