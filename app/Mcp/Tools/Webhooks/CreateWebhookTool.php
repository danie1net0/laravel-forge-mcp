<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Webhooks;

use App\Integrations\Forge\Data\Webhooks\CreateWebhookData;
use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateWebhookTool extends Tool
{
    protected string $description = 'Create a new webhook for deployment notifications.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'url' => ['required', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $data = CreateWebhookData::from($request->except(['server_id', 'site_id']));

        try {
            $webhook = $client->webhooks()->create($serverId, $siteId, $data);

            return Response::text(json_encode([
                'success' => true,
                'webhook' => ['id' => $webhook->id, 'url' => $webhook->url],
                'message' => 'Webhook created successfully',
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()->min(1)->required(),
            'site_id' => $schema->integer()->min(1)->required(),
            'url' => $schema->string()->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
