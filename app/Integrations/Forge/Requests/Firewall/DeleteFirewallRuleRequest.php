<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Firewall;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteFirewallRuleRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $serverId,
        private readonly int $ruleId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/firewall-rules/{$this->ruleId}";
    }
}
