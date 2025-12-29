<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Firewall;

use App\Integrations\Forge\Data\Firewall\FirewallRuleCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListFirewallRulesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/firewall-rules";
    }

    public function createDtoFromResponse(Response $response): FirewallRuleCollectionData
    {
        $rules = array_map(
            fn (array $rule): array => array_merge($rule, ['server_id' => $this->serverId]),
            $response->json('rules')
        );

        return FirewallRuleCollectionData::from(['rules' => $rules]);
    }
}
