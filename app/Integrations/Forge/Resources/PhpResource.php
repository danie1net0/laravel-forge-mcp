<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class PhpResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId): array
    {
        $response = $this->connector->send($this->makeRequest(Method::GET, "/servers/{$serverId}/php"));

        return $response->json();
    }

    public function install(int $serverId, string $version): void
    {
        $this->connector->send(new \App\Integrations\Forge\Requests\Php\InstallPhpRequest($serverId, $version));
    }

    public function update(int $serverId, string $version): void
    {
        $this->connector->send(new \App\Integrations\Forge\Requests\Php\UpdatePhpRequest($serverId, $version));
    }

    public function enableOpcache(int $serverId): void
    {
        $this->connector->send($this->makeRequest(Method::POST, "/servers/{$serverId}/php/opcache"));
    }

    public function disableOpcache(int $serverId): void
    {
        $this->connector->send($this->makeRequest(Method::DELETE, "/servers/{$serverId}/php/opcache"));
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
