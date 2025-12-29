<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Webhooks;

use App\Integrations\Forge\Data\Webhooks\WebhookData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetWebhookRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly int $siteId,
        private readonly int $webhookId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/webhooks/{$this->webhookId}";
    }

    public function createDtoFromResponse(Response $response): WebhookData
    {
        return WebhookData::from(array_merge($response->json('webhook'), ['server_id' => $this->serverId, 'site_id' => $this->siteId]));
    }
}
