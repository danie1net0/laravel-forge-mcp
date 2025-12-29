<?php

declare(strict_types=1);

namespace App\Mcp\Tools\RedirectRules;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use App\Integrations\Forge\Data\RedirectRules\RedirectRuleData;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class ListRedirectRulesTool extends Tool
{
    protected string $description = 'List all redirect rules configured for a site.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $rules = $client->redirectRules()->list($request->integer('server_id'), $request->integer('site_id'))->rules;

            $formatted = array_map(fn (RedirectRuleData $rule): array => [
                'id' => $rule->id,
                'from' => $rule->from,
                'to' => $rule->to,
                'type' => $rule->type,
                'status' => $rule->status,
            ], $rules);

            return Response::text(json_encode([
                'success' => true,
                'redirect_rules' => $formatted,
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
