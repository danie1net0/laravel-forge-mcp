<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\SSHKeys;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteSSHKeyRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $serverId,
        private readonly int $keyId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/keys/{$this->keyId}";
    }
}
