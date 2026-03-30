<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Certificates;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class GetCertificateSigningRequestTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get the Certificate Signing Request (CSR) for a specific domain's SSL certificate.

        A CSR is a block of encoded text that contains information about your organization
        and the domain you want to secure. It's required when ordering SSL certificates
        from Certificate Authorities.

        Returns the CSR in PEM format, which you can:
        - Submit to a Certificate Authority to obtain an SSL certificate
        - Verify certificate details before installation
        - Regenerate certificates if needed

        This is a read-only operation and will not modify any data.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `domain_id`: The unique ID of the domain
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
            $csr = $client->certificates()->signingRequest($serverId, $siteId, $domainId);

            return Response::text((string) json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'domain_id' => $domainId,
                'csr' => $csr,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $exception) {
            return Response::text((string) json_encode([
                'success' => false,
                'error' => $exception->getMessage(),
                'message' => 'Failed to retrieve certificate signing request. The certificate may not exist or may not have a CSR.',
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
                ->description('The unique ID of the domain')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
