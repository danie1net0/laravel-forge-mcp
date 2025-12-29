<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\SecurityRules\{CreateSecurityRuleData, SecurityRuleCollectionData, SecurityRuleData};
use App\Integrations\Forge\Requests\SecurityRules\{CreateSecurityRuleRequest, DeleteSecurityRuleRequest, GetSecurityRuleRequest, ListSecurityRulesRequest};

class SecurityRuleResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId, int $siteId): SecurityRuleCollectionData
    {
        $request = new ListSecurityRulesRequest($serverId, $siteId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $siteId, int $ruleId): SecurityRuleData
    {
        $request = new GetSecurityRuleRequest($serverId, $siteId, $ruleId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(int $serverId, int $siteId, CreateSecurityRuleData $data): SecurityRuleData
    {
        $request = new CreateSecurityRuleRequest($serverId, $siteId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, int $siteId, int $ruleId): void
    {
        $this->connector->send(new DeleteSecurityRuleRequest($serverId, $siteId, $ruleId));
    }
}
