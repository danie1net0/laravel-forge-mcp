<?php

declare(strict_types=1);

namespace App\Mcp\Tools\SecurityRules;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\SecurityRules\CreateSecurityRuleData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateSecurityRuleTool extends Tool
{
    protected string $description = 'Create a new security rule (HTTP basic authentication) for a site.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string'],
            'path' => ['required', 'string'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $data = $request->except(['server_id', 'site_id']);

        try {
            $ruleData = CreateSecurityRuleData::from($data);
            $rule = $client->securityRules()->create($serverId, $siteId, $ruleData);

            return Response::text(json_encode([
                'success' => true,
                'rule' => ['id' => $rule->id, 'name' => $rule->name, 'path' => $rule->path],
                'message' => 'Security rule created successfully',
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
            'name' => $schema->string()->required(),
            'path' => $schema->string()->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
