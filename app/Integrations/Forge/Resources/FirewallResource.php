<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Firewall\{CreateFirewallRuleData, FirewallRuleCollectionData, FirewallRuleData};
use App\Integrations\Forge\Requests\Firewall\{CreateFirewallRuleRequest, DeleteFirewallRuleRequest, GetFirewallRuleRequest, ListFirewallRulesRequest};

class FirewallResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId): FirewallRuleCollectionData
    {
        $request = new ListFirewallRulesRequest($serverId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $ruleId): FirewallRuleData
    {
        $request = new GetFirewallRuleRequest($serverId, $ruleId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(int $serverId, CreateFirewallRuleData $data): FirewallRuleData
    {
        $request = new CreateFirewallRuleRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, int $ruleId): void
    {
        $this->connector->send(new DeleteFirewallRuleRequest($serverId, $ruleId));
    }
}
