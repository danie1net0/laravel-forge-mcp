<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Firewall;

use App\Integrations\Forge\Data\Firewall\{CreateFirewallRuleData, FirewallRuleData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class CreateFirewallRuleRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $serverId,
        private readonly CreateFirewallRuleData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/firewall-rules";
    }

    public function createDtoFromResponse(Response $response): FirewallRuleData
    {
        return FirewallRuleData::from(array_merge($response->json('rule'), ['server_id' => $this->serverId]));
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
