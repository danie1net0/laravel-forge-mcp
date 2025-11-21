<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Certificates;

use App\Services\ForgeService;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Attributes\IsDestructive;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

#[IsDestructive]
class ObtainLetsEncryptCertificateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Obtain and install a free Let's Encrypt SSL certificate for a site.

        Let's Encrypt certificates are free, automated, and widely trusted.
        They auto-renew every 90 days.

        **Requirements:**
        - Domain must be pointed to the server's IP address
        - Port 80 must be accessible for domain verification

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `domains`: Array of domain names to include in the certificate
    MARKDOWN;

    public function handle(Request $request, ForgeService $forge): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'domains' => ['required', 'array', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $domains = $request->array('domains');

        try {
            $certificate = $forge->obtainLetsEncryptCertificate($serverId, $siteId, [
                'domains' => $domains,
            ]);

            return Response::text(json_encode([
                'success' => true,
                'message' => "Let's Encrypt certificate installation initiated.",
                'certificate_id' => $certificate->id ?? null,
                'domains' => $domains,
                'note' => 'Certificate installation may take a few minutes. Check certificate status using list-certificates-tool.',
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to obtain certificate. Ensure domain DNS is pointing to the server.',
            ], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'server_id' => $schema->integer()
                ->description('The unique ID of the Forge server')
                ->minimum(1)
                ->required(),
            'site_id' => $schema->integer()
                ->description('The unique ID of the site')
                ->minimum(1)
                ->required(),
            'domains' => $schema->array()
                ->items($schema->string())
                ->description('Array of domain names to include in the certificate (e.g., ["example.com", "www.example.com"])')
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
