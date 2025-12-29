<?php

namespace App\Integrations\Forge\Requests\Servers;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class RebootServerRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/reboot";
    }
}
