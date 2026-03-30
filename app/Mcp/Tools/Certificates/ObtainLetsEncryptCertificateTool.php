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
class ObtainLetsEncryptCertificateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Enable a Let's Encrypt SSL certificate for a domain.

        **What is Let's Encrypt?**
        - Free SSL certificates (no cost, ever)
        - Automatically renews every 90 days
        - Trusted by all major browsers
        - Industry standard for HTTPS

        **Common Use Cases:**
        - Enabling HTTPS for a new site
        - Securing a domain after DNS is configured
        - Replacing an expired or soon-to-expire certificate

        **Requirements (CRITICAL - Must be met before attempting):**
        - Domain DNS must point to server's IP address
        - Port 80 must be open (for domain verification)
        - Site must be accessible via HTTP first
        - Domain must be publicly accessible (not localhost)

        **Before You Run This:**
        1. Verify DNS is propagated: `dig yourdomain.com` or `nslookup yourdomain.com`
        2. Check site responds on HTTP: `curl http://yourdomain.com`
        3. Verify port 80 is open: Use `list-firewall-rules-tool`

        **Common Errors & Solutions:**
        - "Connection refused" -> DNS not pointing to server, wait for propagation
        - "Port 80 blocked" -> Add firewall rule for port 80
        - "Too many requests" -> Let's Encrypt rate limit (5 failures/hour), wait 1 hour
        - "Invalid domain" -> Domain must be a valid FQDN, not IP address

        **What Happens:**
        1. Forge enables Let's Encrypt certificate on the domain
        2. Let's Encrypt verifies domain ownership (HTTP challenge)
        3. Certificate is issued (takes 1-3 minutes)
        4. Forge installs and activates certificate
        5. Site is now accessible via HTTPS
        6. HTTP traffic automatically redirects to HTTPS

        **After Installation:**
        - Certificate automatically renews every 90 days
        - No manual intervention needed
        - Check status with `list-certificates-tool`

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
            $certificate = $client->certificates()->obtainLetsEncrypt($serverId, $siteId, $domainId);

            return Response::text((string) json_encode([
                'success' => true,
                'message' => "Let's Encrypt certificate installation initiated.",
                'certificate_id' => $certificate->id,
                'domain_id' => $domainId,
                'note' => 'Certificate installation may take a few minutes. Check certificate status using list-certificates-tool.',
            ], JSON_PRETTY_PRINT));
        } catch (Exception $exception) {
            return Response::text((string) json_encode([
                'success' => false,
                'error' => $exception->getMessage(),
                'message' => 'Failed to obtain certificate. Ensure domain DNS is pointing to the server.',
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
