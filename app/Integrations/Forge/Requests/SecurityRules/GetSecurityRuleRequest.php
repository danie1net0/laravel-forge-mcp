<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\SecurityRules;

use App\Integrations\Forge\Data\SecurityRules\SecurityRuleData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetSecurityRuleRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly int $siteId,
        private readonly int $ruleId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/security-rules/{$this->ruleId}";
    }

    public function createDtoFromResponse(Response $response): SecurityRuleData
    {
        return SecurityRuleData::from(array_merge($response->json('rule'), ['server_id' => $this->serverId, 'site_id' => $this->siteId]));
    }
}
