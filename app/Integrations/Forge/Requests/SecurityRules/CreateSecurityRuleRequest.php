<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\SecurityRules;

use App\Integrations\Forge\Data\SecurityRules\{CreateSecurityRuleData, SecurityRuleData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class CreateSecurityRuleRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $serverId,
        private readonly int $siteId,
        private readonly CreateSecurityRuleData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/security-rules";
    }

    public function createDtoFromResponse(Response $response): SecurityRuleData
    {
        return SecurityRuleData::from(array_merge($response->json('rule'), ['server_id' => $this->serverId, 'site_id' => $this->siteId]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return array_filter(
            $this->data->toArray(),
            fn (mixed $value): bool => $value !== null
        );
    }
}
