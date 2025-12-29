<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Servers;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetEventOutputRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected int $eventId
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/events/{$this->eventId}";
    }
}
