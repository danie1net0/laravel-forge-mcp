<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Webhooks;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class GetWebhookTool extends Tool
{
    protected string $description = 'Get information about a specific webhook.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'webhook_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $webhook = $client->webhooks()->get(
                $request->integer('server_id'),
                $request->integer('site_id'),
                $request->integer('webhook_id')
            );

            return Response::text(json_encode([
                'success' => true,
                'webhook' => ['id' => $webhook->id, 'url' => $webhook->url],
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
            'webhook_id' => $schema->integer()->min(1)->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
