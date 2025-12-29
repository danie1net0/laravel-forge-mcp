<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Workers;

use App\Integrations\Forge\Data\Workers\WorkerCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListWorkersRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly int $siteId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/workers";
    }

    public function createDtoFromResponse(Response $response): WorkerCollectionData
    {
        $workers = array_map(
            fn (array $worker): array => array_merge($worker, ['server_id' => $this->serverId, 'site_id' => $this->siteId]),
            $response->json('workers')
        );

        return WorkerCollectionData::from(['workers' => $workers]);
    }
}
