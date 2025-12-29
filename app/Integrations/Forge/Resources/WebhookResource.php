<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Webhooks\{CreateWebhookData, WebhookCollectionData, WebhookData};
use App\Integrations\Forge\Requests\Webhooks\{CreateWebhookRequest, DeleteWebhookRequest, GetWebhookRequest, ListWebhooksRequest};

class WebhookResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId, int $siteId): WebhookCollectionData
    {
        $request = new ListWebhooksRequest($serverId, $siteId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $siteId, int $webhookId): WebhookData
    {
        $request = new GetWebhookRequest($serverId, $siteId, $webhookId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(int $serverId, int $siteId, CreateWebhookData $data): WebhookData
    {
        $request = new CreateWebhookRequest($serverId, $siteId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, int $siteId, int $webhookId): void
    {
        $this->connector->send(new DeleteWebhookRequest($serverId, $siteId, $webhookId));
    }
}
