<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class IntegrationResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function getHorizon(int $serverId, int $siteId): array
    {
        return $this->get($serverId, $siteId, 'horizon');
    }

    public function enableHorizon(int $serverId, int $siteId): void
    {
        $this->enable($serverId, $siteId, 'horizon');
    }

    public function disableHorizon(int $serverId, int $siteId): void
    {
        $this->disable($serverId, $siteId, 'horizon');
    }

    public function getOctane(int $serverId, int $siteId): array
    {
        return $this->get($serverId, $siteId, 'octane');
    }

    public function enableOctane(int $serverId, int $siteId, string $server = 'swoole', string|int $workers = 'auto'): void
    {
        $this->connector->send(new \App\Integrations\Forge\Requests\Integrations\EnableOctaneRequest(
            $serverId,
            $siteId,
            $server,
            $workers
        ));
    }

    public function disableOctane(int $serverId, int $siteId): void
    {
        $this->disable($serverId, $siteId, 'octane');
    }

    public function getReverb(int $serverId, int $siteId): array
    {
        return $this->get($serverId, $siteId, 'reverb');
    }

    public function enableReverb(int $serverId, int $siteId): void
    {
        $this->enable($serverId, $siteId, 'reverb');
    }

    public function disableReverb(int $serverId, int $siteId): void
    {
        $this->disable($serverId, $siteId, 'reverb');
    }

    public function getPulse(int $serverId, int $siteId): array
    {
        return $this->get($serverId, $siteId, 'pulse');
    }

    public function enablePulse(int $serverId, int $siteId): void
    {
        $this->enable($serverId, $siteId, 'pulse');
    }

    public function disablePulse(int $serverId, int $siteId): void
    {
        $this->disable($serverId, $siteId, 'pulse');
    }

    public function getInertia(int $serverId, int $siteId): array
    {
        return $this->get($serverId, $siteId, 'inertia');
    }

    public function enableInertia(int $serverId, int $siteId): void
    {
        $this->enable($serverId, $siteId, 'inertia');
    }

    public function disableInertia(int $serverId, int $siteId): void
    {
        $this->disable($serverId, $siteId, 'inertia');
    }

    public function getMaintenance(int $serverId, int $siteId): array
    {
        return $this->get($serverId, $siteId, 'laravel-maintenance');
    }

    public function enableMaintenance(int $serverId, int $siteId, ?string $secret = null, ?string $refresh = null): void
    {
        $this->connector->send(new \App\Integrations\Forge\Requests\Integrations\EnableMaintenanceRequest(
            $serverId,
            $siteId,
            $secret,
            $refresh
        ));
    }

    public function disableMaintenance(int $serverId, int $siteId): void
    {
        $this->disable($serverId, $siteId, 'laravel-maintenance');
    }

    public function getScheduler(int $serverId, int $siteId): array
    {
        return $this->get($serverId, $siteId, 'laravel-scheduler');
    }

    public function enableScheduler(int $serverId, int $siteId): void
    {
        $this->enable($serverId, $siteId, 'laravel-scheduler');
    }

    public function disableScheduler(int $serverId, int $siteId): void
    {
        $this->disable($serverId, $siteId, 'laravel-scheduler');
    }

    private function get(int $serverId, int $siteId, string $integration): array
    {
        $response = $this->connector->send(
            $this->makeRequest(Method::GET, "/servers/{$serverId}/sites/{$siteId}/integrations/{$integration}")
        );

        return $response->json();
    }

    private function enable(int $serverId, int $siteId, string $integration): void
    {
        $this->connector->send(
            $this->makeRequest(Method::POST, "/servers/{$serverId}/sites/{$siteId}/integrations/{$integration}")
        );
    }

    private function disable(int $serverId, int $siteId, string $integration): void
    {
        $this->connector->send(
            $this->makeRequest(Method::DELETE, "/servers/{$serverId}/sites/{$siteId}/integrations/{$integration}")
        );
    }

    private function makeRequest(Method $method, string $endpoint): Request
    {
        return new class($method, $endpoint) extends Request {
            public function __construct(
                protected Method $method,
                protected string $endpoint
            ) {
            }

            public function resolveEndpoint(): string
            {
                return $this->endpoint;
            }
        };
    }
}
