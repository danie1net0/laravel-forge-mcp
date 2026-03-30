<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Requests\Services\{InstallBlackfireRequest, InstallPapertrailRequest, ManageServiceRequest};
use Saloon\Enums\Method;
use Saloon\Http\Request;

class ServiceResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function rebootMysql(int $serverId): void
    {
        $this->connector->send(new ManageServiceRequest($serverId, 'mysql', 'reboot'));
    }

    public function stopMysql(int $serverId): void
    {
        $this->connector->send(new ManageServiceRequest($serverId, 'mysql', 'stop'));
    }

    public function rebootNginx(int $serverId): void
    {
        $this->connector->send(new ManageServiceRequest($serverId, 'nginx', 'reboot'));
    }

    public function stopNginx(int $serverId): void
    {
        $this->connector->send(new ManageServiceRequest($serverId, 'nginx', 'stop'));
    }

    public function testNginx(int $serverId): array
    {
        $response = $this->connector->send($this->makeRequest(Method::GET, "/servers/{$serverId}/nginx/test"));

        return $response->json();
    }

    public function rebootPostgres(int $serverId): void
    {
        $this->connector->send(new ManageServiceRequest($serverId, 'postgres', 'reboot'));
    }

    public function stopPostgres(int $serverId): void
    {
        $this->connector->send(new ManageServiceRequest($serverId, 'postgres', 'stop'));
    }

    public function rebootPhp(int $serverId): void
    {
        $this->connector->send(new ManageServiceRequest($serverId, 'php', 'reboot'));
    }

    public function installBlackfire(int $serverId, string $serverIdToken, string $serverToken): void
    {
        $this->connector->send(new InstallBlackfireRequest(
            $serverId,
            $serverIdToken,
            $serverToken
        ));
    }

    public function removeBlackfire(int $serverId): void
    {
        $this->connector->send($this->makeRequest(Method::DELETE, "/servers/{$serverId}/blackfire/remove"));
    }

    public function installPapertrail(int $serverId, string $host): void
    {
        $this->connector->send(new InstallPapertrailRequest($serverId, $host));
    }

    public function removePapertrail(int $serverId): void
    {
        $this->connector->send($this->makeRequest(Method::DELETE, "/servers/{$serverId}/papertrail/remove"));
    }

    public function startService(int $serverId, string $service): void
    {
        $this->connector->send(new ManageServiceRequest($serverId, $service, 'start'));
    }

    public function stopService(int $serverId, string $service): void
    {
        $this->connector->send(new ManageServiceRequest($serverId, $service, 'stop'));
    }

    public function restartService(int $serverId, string $service): void
    {
        $this->connector->send(new ManageServiceRequest($serverId, $service, 'restart'));
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
