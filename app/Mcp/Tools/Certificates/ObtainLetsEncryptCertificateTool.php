<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Certificates;

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Data\Certificates\ObtainLetsEncryptCertificateData;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

#[IsDestructive]
class ObtainLetsEncryptCertificateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Obtain and install a free Let's Encrypt SSL certificate for a site.

        **What is Let's Encrypt?**
        - Free SSL certificates (no cost, ever)
        - Automatically renews every 90 days
        - Trusted by all major browsers
        - Industry standard for HTTPS

        **Common Use Cases:**
        - Enabling HTTPS for a new site
        - Securing www and non-www domains together
        - Adding SSL to a site after domain DNS is configured
        - Replacing an expired or soon-to-expire certificate

        **Requirements (CRITICAL - Must be met before attempting):**
        ✅ Domain DNS must point to server's IP address
        ✅ Port 80 must be open (for domain verification)
        ✅ Site must be accessible via HTTP first
        ✅ Domain must be publicly accessible (not localhost)

        **Before You Run This:**
        1. Verify DNS is propagated: `dig yourdomain.com` or `nslookup yourdomain.com`
        2. Check site responds on HTTP: `curl http://yourdomain.com`
        3. Verify port 80 is open: Use `list-firewall-rules-tool`

        **Common Errors & Solutions:**
        - "Connection refused" → DNS not pointing to server, wait for propagation
        - "Port 80 blocked" → Add firewall rule for port 80
        - "Too many requests" → Let's Encrypt rate limit (5 failures/hour), wait 1 hour
        - "Invalid domain" → Domain must be a valid FQDN, not IP address

        **Examples:**
        - Single domain: `["example.com"]`
        - With www: `["example.com", "www.example.com"]`
        - Multiple subdomains: `["example.com", "www.example.com", "api.example.com"]`

        **What Happens:**
        1. Forge requests certificate from Let's Encrypt
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
        - `domains`: Array of domain names to include in the certificate
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
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
            $obtainData = ObtainLetsEncryptCertificateData::from([
                'domains' => $domains,
            ]);
            $certificate = $client->certificates()->obtainLetsEncrypt($serverId, $siteId, $obtainData);

            return Response::text(json_encode([
                'success' => true,
                'message' => "Let's Encrypt certificate installation initiated.",
                'certificate_id' => $certificate->id,
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
                ->min(1)
                ->required(),
            'site_id' => $schema->integer()
                ->description('The unique ID of the site')
                ->min(1)
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
