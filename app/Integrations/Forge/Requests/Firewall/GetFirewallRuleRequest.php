<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Firewall;

use App\Integrations\Forge\Data\Firewall\FirewallRuleData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetFirewallRuleRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly int $ruleId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/firewall-rules/{$this->ruleId}";
    }

    public function createDtoFromResponse(Response $response): FirewallRuleData
    {
        return FirewallRuleData::from(array_merge($response->json('rule'), ['server_id' => $this->serverId]));
    }
}
