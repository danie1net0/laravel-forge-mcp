<?php

declare(strict_types=1);

namespace App\Mcp\Tools\NginxTemplates;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\NginxTemplates\UpdateNginxTemplateData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class UpdateNginxTemplateTool extends Tool
{
    protected string $description = 'Update an existing Nginx template.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'template_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $templateId = $request->integer('template_id');
        $data = $request->except(['server_id', 'template_id']);

        try {
            $templateData = UpdateNginxTemplateData::from($data);
            $template = $client->nginxTemplates()->update($serverId, $templateId, $templateData);

            return Response::text(json_encode([
                'success' => true,
                'template' => ['id' => $template->id, 'name' => $template->name],
                'message' => 'Nginx template updated successfully',
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
            'name' => $schema->string(),
            'content' => $schema->string(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
