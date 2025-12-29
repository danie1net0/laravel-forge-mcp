<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\RedirectRules;

use App\Integrations\Forge\Data\RedirectRules\RedirectRuleCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListRedirectRulesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly int $siteId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/redirect-rules";
    }

    public function createDtoFromResponse(Response $response): RedirectRuleCollectionData
    {
        $rules = array_map(
            fn (array $rule): array => array_merge($rule, ['server_id' => $this->serverId, 'site_id' => $this->siteId]),
            $response->json('redirect_rules')
        );

        return RedirectRuleCollectionData::from(['redirect_rules' => $rules]);
    }
}
