<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\SSHKeys;

use App\Integrations\Forge\Data\SSHKeys\SSHKeyCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListSSHKeysRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/keys";
    }

    public function createDtoFromResponse(Response $response): SSHKeyCollectionData
    {
        $keys = array_map(
            fn (array $key): array => array_merge($key, ['server_id' => $this->serverId]),
            $response->json('keys')
        );

        return SSHKeyCollectionData::from(['keys' => $keys]);
    }
}
