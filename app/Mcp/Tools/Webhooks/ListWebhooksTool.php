<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Webhooks;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use App\Integrations\Forge\Data\Webhooks\WebhookData;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class ListWebhooksTool extends Tool
{
    protected string $description = 'List all webhooks configured for a site.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $webhooks = $client->webhooks()->list($request->integer('server_id'), $request->integer('site_id'))->webhooks;

            $formatted = array_map(fn (WebhookData $webhook): array => [
                'id' => $webhook->id,
                'url' => $webhook->url,
            ], $webhooks);

            return Response::text(json_encode([
                'success' => true,
                'webhooks' => $formatted,
                'count' => count($formatted),
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
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
