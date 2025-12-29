<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Workers;

use App\Integrations\Forge\Data\Workers\{CreateWorkerData, WorkerData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class CreateWorkerRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $serverId,
        private readonly int $siteId,
        private readonly CreateWorkerData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/workers";
    }

    public function createDtoFromResponse(Response $response): WorkerData
    {
        return WorkerData::from(array_merge($response->json('worker'), ['server_id' => $this->serverId, 'site_id' => $this->siteId]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return array_filter(
            $this->data->toArray(),
            fn (mixed $value): bool => $value !== null
        );
    }
}
