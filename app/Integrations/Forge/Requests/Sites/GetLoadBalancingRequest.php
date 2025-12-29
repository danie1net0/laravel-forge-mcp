<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Sites;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetLoadBalancingRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected int $siteId
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/balancing";
    }
}
