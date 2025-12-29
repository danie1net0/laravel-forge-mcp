<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Sites;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdateLoadBalancingRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected array $servers,
        protected string $balancingMethod = 'round_robin'
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/balancing";
    }

    protected function defaultBody(): array
    {
        return [
            'servers' => $this->servers,
            'method' => $this->balancingMethod,
        ];
    }
}
