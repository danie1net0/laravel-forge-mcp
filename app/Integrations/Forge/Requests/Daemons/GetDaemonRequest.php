<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Daemons;

use App\Integrations\Forge\Data\Daemons\DaemonData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetDaemonRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly int $daemonId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/daemons/{$this->daemonId}";
    }

    public function createDtoFromResponse(Response $response): DaemonData
    {
        return DaemonData::from(array_merge($response->json('daemon'), ['server_id' => $this->serverId]));
    }
}
