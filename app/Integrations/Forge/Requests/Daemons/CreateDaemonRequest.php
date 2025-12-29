<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Daemons;

use App\Integrations\Forge\Data\Daemons\{CreateDaemonData, DaemonData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class CreateDaemonRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $serverId,
        private readonly CreateDaemonData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/daemons";
    }

    public function createDtoFromResponse(Response $response): DaemonData
    {
        return DaemonData::from(array_merge($response->json('daemon'), ['server_id' => $this->serverId]));
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
