<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Webhooks;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteWebhookRequest extends Request
{
    protected Method $method = Method::DELETE;

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
}
