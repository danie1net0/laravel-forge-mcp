<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\RedirectRules\{CreateRedirectRuleData, RedirectRuleCollectionData, RedirectRuleData};
use App\Integrations\Forge\Requests\RedirectRules\{CreateRedirectRuleRequest, DeleteRedirectRuleRequest, GetRedirectRuleRequest, ListRedirectRulesRequest};

class RedirectRuleResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId, int $siteId): RedirectRuleCollectionData
    {
        $request = new ListRedirectRulesRequest($serverId, $siteId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $siteId, int $ruleId): RedirectRuleData
    {
        $request = new GetRedirectRuleRequest($serverId, $siteId, $ruleId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(int $serverId, int $siteId, CreateRedirectRuleData $data): RedirectRuleData
    {
        $request = new CreateRedirectRuleRequest($serverId, $siteId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, int $siteId, int $ruleId): void
    {
        $this->connector->send(new DeleteRedirectRuleRequest($serverId, $siteId, $ruleId));
    }
}
