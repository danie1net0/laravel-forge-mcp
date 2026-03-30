<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\SecurityRules;

use App\Integrations\Forge\Data\SecurityRules\SecurityRuleCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListSecurityRulesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly int $siteId,
        private readonly ?string $cursor = null,
        private readonly int $pageSize = 30,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/security-rules";
    }

    public function createDtoFromResponse(Response $response): SecurityRuleCollectionData
    {
        $rules = array_map(
            fn (array $rule): array => array_merge($rule, ['server_id' => $this->serverId, 'site_id' => $this->siteId]),
            $response->json('security_rules')
        );

        return SecurityRuleCollectionData::from(['rules' => $rules]);
    }

    /**
     * @return array<string, string|int>
     */
    protected function defaultQuery(): array
    {
        $query = ['page[size]' => $this->pageSize];

        if ($this->cursor !== null) {
            $query['page[cursor]'] = $this->cursor;
        }

        return $query;
    }
}
