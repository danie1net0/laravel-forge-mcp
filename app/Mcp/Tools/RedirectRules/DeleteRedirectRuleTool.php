<?php

declare(strict_types=1);

namespace App\Mcp\Tools\RedirectRules;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteRedirectRuleTool extends Tool
{
    protected string $description = 'Delete a redirect rule from a site.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'rule_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $client->redirectRules()->delete(
                $request->integer('server_id'),
                $request->integer('site_id'),
                $request->integer('rule_id')
            );

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Redirect rule deleted successfully',
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
            'rule_id' => $schema->integer()->min(1)->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
