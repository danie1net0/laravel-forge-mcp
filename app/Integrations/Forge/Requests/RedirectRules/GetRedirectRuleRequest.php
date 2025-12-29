<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\RedirectRules;

use App\Integrations\Forge\Data\RedirectRules\RedirectRuleData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetRedirectRuleRequest extends Request
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
        return "/servers/{$this->serverId}/sites/{$this->siteId}/redirect-rules/{$this->ruleId}";
    }

    public function createDtoFromResponse(Response $response): RedirectRuleData
    {
        return RedirectRuleData::from(array_merge($response->json('rule'), ['server_id' => $this->serverId, 'site_id' => $this->siteId]));
    }
}
