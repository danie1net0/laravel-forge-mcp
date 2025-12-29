<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\SSHKeys;

use App\Integrations\Forge\Data\SSHKeys\SSHKeyData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetSSHKeyRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly int $keyId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/keys/{$this->keyId}";
    }

    public function createDtoFromResponse(Response $response): SSHKeyData
    {
        return SSHKeyData::from(array_merge($response->json('key'), ['server_id' => $this->serverId]));
    }
}
