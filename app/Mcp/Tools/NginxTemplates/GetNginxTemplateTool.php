<?php

declare(strict_types=1);

namespace App\Mcp\Tools\NginxTemplates;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class GetNginxTemplateTool extends Tool
{
    protected string $description = 'Get a specific Nginx template.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'template_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $template = $client->nginxTemplates()->get(
                $request->integer('server_id'),
                $request->integer('template_id')
            );

            return Response::text(json_encode([
                'success' => true,
                'template' => ['id' => $template->id, 'name' => $template->name],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()->min(1)->required(),
            'template_id' => $schema->integer()->min(1)->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
