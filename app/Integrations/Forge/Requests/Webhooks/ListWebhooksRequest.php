<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Webhooks;

use App\Integrations\Forge\Data\Webhooks\WebhookCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListWebhooksRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly int $siteId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/webhooks";
    }

    public function createDtoFromResponse(Response $response): WebhookCollectionData
    {
        $webhooks = array_map(
            fn (array $webhook): array => array_merge($webhook, ['server_id' => $this->serverId, 'site_id' => $this->siteId]),
            $response->json('webhooks')
        );

        return WebhookCollectionData::from(['webhooks' => $webhooks]);
    }
}
