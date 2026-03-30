<?php

declare(strict_types=1);

namespace App\Mcp\Tools\NginxTemplates;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\Integrations\Forge\Data\NginxTemplates\NginxTemplateData;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class ListNginxTemplatesTool extends Tool
{
    protected string $description = 'List all Nginx templates on a server.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate(['server_id' => ['required', 'integer', 'min:1']]);

        $cursor = $request->has('cursor') ? $request->string('cursor')->value() : null;
        $pageSize = $request->has('page_size') ? $request->integer('page_size') : 30;

        try {
            $templates = $client->nginxTemplates()->list($request->integer('server_id'), $cursor, $pageSize)->templates;

            $formatted = array_map(fn (NginxTemplateData $t): array => [
                'id' => $t->id,
                'name' => $t->name,
            ], $templates);

            return Response::text((string) json_encode([
                'success' => true,
                'templates' => $formatted,
                'count' => count($formatted),
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text((string) json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()->min(1)->required(),
            'cursor' => $schema->string()->description('Pagination cursor for next page')->nullable(),
            'page_size' => $schema->integer()->description('Items per page (default 30)')->min(1)->max(100)->nullable(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
