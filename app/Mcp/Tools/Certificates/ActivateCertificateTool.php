<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Certificates;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class ActivateCertificateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Activate an SSL certificate for a domain.

        This will configure the domain to use SSL for HTTPS.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `domain_id`: The unique ID of the domain to activate the certificate on
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'domain_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $domainId = $request->integer('domain_id');

        try {
            $client->certificates()->activate($serverId, $siteId, $domainId);

            return Response::text((string) json_encode([
                'success' => true,
                'message' => 'Certificate activated successfully.',
                'server_id' => $serverId,
                'site_id' => $siteId,
                'domain_id' => $domainId,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $exception) {
            return Response::text((string) json_encode([
                'success' => false,
                'error' => $exception->getMessage(),
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
            'domain_id' => $schema->integer()
                ->description('The unique ID of the domain to activate the certificate on')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
