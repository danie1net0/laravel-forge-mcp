<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Certificates;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteCertificateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Delete an SSL certificate from a site.

        ⚠️ **WARNING** ⚠️

        This will:
        - Remove the certificate from Forge
        - Site will revert to HTTP (no HTTPS)
        - Cannot be undone

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `certificate_id`: The unique ID of the certificate to delete

        Ensure you have a replacement certificate before deleting the active one.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'certificate_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $certificateId = $request->integer('certificate_id');

        try {
            $client->certificates()->delete($serverId, $siteId, $certificateId);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Certificate deleted successfully',
                'warning' => 'Site may now be serving HTTP instead of HTTPS',
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
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
            'certificate_id' => $schema->integer()
                ->description('The unique ID of the certificate to delete')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
