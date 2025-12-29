<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\RedirectRules;

use App\Integrations\Forge\Data\RedirectRules\{CreateRedirectRuleData, RedirectRuleData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class CreateRedirectRuleRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $serverId,
        private readonly int $siteId,
        private readonly CreateRedirectRuleData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/redirect-rules";
    }

    public function createDtoFromResponse(Response $response): RedirectRuleData
    {
        return RedirectRuleData::from(array_merge($response->json('rule'), ['server_id' => $this->serverId, 'site_id' => $this->siteId]));
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
