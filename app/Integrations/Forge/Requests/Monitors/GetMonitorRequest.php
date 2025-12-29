<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Monitors;

use App\Integrations\Forge\Data\Monitors\MonitorData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetMonitorRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly int $monitorId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/monitors/{$this->monitorId}";
    }

    public function createDtoFromResponse(Response $response): MonitorData
    {
        return MonitorData::from(array_merge($response->json('monitor'), ['server_id' => $this->serverId]));
    }
}
