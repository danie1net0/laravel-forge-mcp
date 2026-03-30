<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Servers;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetServerLogRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly string $logKey = 'auth',
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/logs/{$this->logKey}";
    }
}
