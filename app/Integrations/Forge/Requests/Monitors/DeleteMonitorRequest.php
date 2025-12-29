<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Monitors;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteMonitorRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $serverId,
        private readonly int $monitorId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/monitors/{$this->monitorId}";
    }
}
