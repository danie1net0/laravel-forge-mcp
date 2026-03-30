<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Certificates;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteCertificateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Disable and remove an SSL certificate from a domain.

        **WARNING**

        This will:
        - Disable the certificate on the domain
        - Site will revert to HTTP (no HTTPS)
        - Cannot be undone

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `domain_id`: The unique ID of the domain to disable the certificate on

        Ensure you have a replacement certificate before disabling the active one.
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
            $client->certificates()->delete($serverId, $siteId, $domainId);

            return Response::text((string) json_encode([
                'success' => true,
                'message' => 'Certificate deleted successfully',
                'warning' => 'Site may now be serving HTTP instead of HTTPS',
            ], JSON_PRETTY_PRINT));
        } catch (Exception $exception) {
            return Response::text((string) json_encode([
                'success' => false,
                'error' => $exception->getMessage(),
                'message' => 'Failed to delete certificate.',
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
                ->description('The unique ID of the domain to disable the certificate on')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
