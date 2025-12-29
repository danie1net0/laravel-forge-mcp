<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Daemons;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteDaemonRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $serverId,
        private readonly int $daemonId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/daemons/{$this->daemonId}";
    }
}
