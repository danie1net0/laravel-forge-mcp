<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Daemons;

use App\Integrations\Forge\Data\Daemons\DaemonCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListDaemonsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/daemons";
    }

    public function createDtoFromResponse(Response $response): DaemonCollectionData
    {
        $daemons = array_map(
            fn (array $daemon): array => array_merge($daemon, ['server_id' => $this->serverId]),
            $response->json('daemons')
        );

        return DaemonCollectionData::from(['daemons' => $daemons]);
    }
}
