<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Workers;

use App\Integrations\Forge\Data\Workers\WorkerData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetWorkerRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly int $siteId,
        private readonly int $workerId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/workers/{$this->workerId}";
    }

    public function createDtoFromResponse(Response $response): WorkerData
    {
        return WorkerData::from(array_merge($response->json('worker'), ['server_id' => $this->serverId, 'site_id' => $this->siteId]));
    }
}
