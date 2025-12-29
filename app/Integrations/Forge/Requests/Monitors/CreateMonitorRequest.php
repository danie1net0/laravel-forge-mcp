<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Monitors;

use App\Integrations\Forge\Data\Monitors\{CreateMonitorData, MonitorData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class CreateMonitorRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $serverId,
        private readonly CreateMonitorData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/monitors";
    }

    public function createDtoFromResponse(Response $response): MonitorData
    {
        return MonitorData::from(array_merge($response->json('monitor'), ['server_id' => $this->serverId]));
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
