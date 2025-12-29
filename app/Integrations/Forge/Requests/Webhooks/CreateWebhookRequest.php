<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Webhooks;

use App\Integrations\Forge\Data\Webhooks\{CreateWebhookData, WebhookData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class CreateWebhookRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $serverId,
        private readonly int $siteId,
        private readonly CreateWebhookData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/webhooks";
    }

    public function createDtoFromResponse(Response $response): WebhookData
    {
        return WebhookData::from(array_merge($response->json('webhook'), ['server_id' => $this->serverId, 'site_id' => $this->siteId]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
