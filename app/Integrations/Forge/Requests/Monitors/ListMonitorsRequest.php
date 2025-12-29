<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Monitors;

use App\Integrations\Forge\Data\Monitors\MonitorCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListMonitorsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/monitors";
    }

    public function createDtoFromResponse(Response $response): MonitorCollectionData
    {
        $monitors = array_map(
            fn (array $monitor): array => array_merge($monitor, ['server_id' => $this->serverId]),
            $response->json('monitors')
        );

        return MonitorCollectionData::from(['monitors' => $monitors]);
    }
}
