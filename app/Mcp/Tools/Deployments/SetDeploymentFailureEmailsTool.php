<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Deployments;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class SetDeploymentFailureEmailsTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Set email addresses to be notified when a deployment fails.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `emails`: Array of email addresses to notify

        These emails will receive notifications when deployments fail.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'emails' => ['required', 'array'],
            'emails.*' => ['email'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $emails = $request->collect('emails')->toArray();

        try {
            $client->sites()->setDeploymentFailureEmails($serverId, $siteId, $emails);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Deployment failure notification emails set successfully.',
                'server_id' => $serverId,
                'site_id' => $siteId,
                'emails' => $emails,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()
                ->description('The unique ID of the Forge server')
                ->min(1)
                ->required(),
            'site_id' => $schema->integer()
                ->description('The unique ID of the site')
                ->min(1)
                ->required(),
            'emails' => $schema->array()
                ->items($schema->string()->format('email'))
                ->description('Array of email addresses to notify on deployment failure')
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
