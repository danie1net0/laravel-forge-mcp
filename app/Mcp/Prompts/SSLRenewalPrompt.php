<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class SSLRenewalPrompt extends Prompt
{
    protected string $name = 'ssl-renewal';

    protected string $description = 'SSL certificate renewal and management workflow';

    public function handle(Request $request): array
    {
        $serverId = $request->string('server_id', '');
        $siteId = $request->string('site_id', '');
        $certificateType = $request->string('certificate_type', 'letsencrypt');

        $workflow = "# SSL Certificate Renewal Workflow\n\n";

        if ($serverId->isEmpty() || $siteId->isEmpty()) {
            $workflow .= <<<'MD'
            ## Step 1: Identify Site and Current Certificate

            1. Use `list-servers-tool` to find the server
            2. Use `list-sites-tool` to find the site
            3. Use `list-certificates-tool` to see current certificates

            MD;
        }

        $workflow .= <<<'MD'
        ## Step 2: Check Certificate Status

        1. Use `get-certificate-tool` to get certificate details:
           - Check expiration date
           - Check certificate type (Let's Encrypt, custom)
           - Note domains covered by the certificate

        2. Identify certificate issues:
           - Expired or expiring soon (< 30 days)
           - Domain mismatch
           - Chain certificate issues

        ## Step 3: Let's Encrypt Certificates

        For Let's Encrypt certificates (auto-renewing):

        3. If certificate is Let's Encrypt and expired:
           - Check if auto-renewal failed
           - Common issues:
             - DNS not pointing to server
             - Site returning 404
             - Firewall blocking port 80

        4. To renew/obtain Let's Encrypt certificate:
           - Use `obtain-lets-encrypt-certificate-tool`
           - Provide all domains the site should respond to
           - Include www and non-www if applicable

        5. Verify renewal succeeded:
           - Use `list-certificates-tool` to check status
           - Status should show "installed"

        ## Step 4: Custom SSL Certificates

        For custom/purchased certificates:

        6. If using a custom certificate:
           - Obtain new certificate from CA (DigiCert, Comodo, etc.)
           - You'll need:
             - Certificate (CRT file)
             - Private Key (KEY file)
             - CA Bundle/Chain (optional but recommended)

        7. Use `install-certificate-tool` with:
           - certificate: PEM-encoded certificate
           - private_key: PEM-encoded private key
           - Add intermediate certificates if provided

        ## Step 5: Certificate Activation

        8. After installing new certificate:
           - Use `activate-certificate-tool` to make it active
           - This switches the site to use the new certificate

        9. Verify activation:
           - Use `get-site-tool` to confirm SSL is active
           - Test HTTPS access to the site

        ## Step 6: Common SSL Issues and Solutions

        ### Let's Encrypt Errors

        **"Challenge failed"**
        - Ensure DNS points to this server
        - Ensure site responds on port 80
        - Check no redirect rules blocking .well-known/acme-challenge

        **"Too many certificates already issued"**
        - Rate limited by Let's Encrypt
        - Wait 1 hour and try again
        - Consider using wildcard certificate

        **"Unauthorized"**
        - Domain ownership verification failed
        - Ensure DNS is properly configured
        - Check AAAA records match if using IPv6

        ### Custom Certificate Errors

        **"Certificate and private key do not match"**
        - Ensure you're using the correct key pair
        - Regenerate CSR and certificate if needed

        **"Certificate chain incomplete"**
        - Include intermediate certificates
        - Concatenate in correct order: cert, intermediate, root

        ## Step 7: Cleanup Old Certificates

        10. After successful renewal:
            - Use `delete-certificate-tool` to remove old certificates
            - Keep only the active certificate to avoid confusion

        ## Step 8: Setup Monitoring

        11. Prevent future issues:
            - Let's Encrypt auto-renews 30 days before expiry
            - Set up external SSL monitoring (SSLLabs, etc.)
            - Consider `create-monitor-tool` for HTTPS monitoring

        ## SSL Best Practices

        - Use TLS 1.2 or higher only
        - Enable HSTS after confirming HTTPS works
        - Use strong cipher suites
        - Consider OCSP stapling for performance
        - Wildcard certificates for multiple subdomains

        MD;

        if ($certificateType->value() === 'custom') {
            $workflow .= <<<'MD'

            ## Custom Certificate Focus

            For your custom certificate, you'll need:
            1. Generate CSR: `get-certificate-signing-request-tool`
            2. Submit CSR to your Certificate Authority
            3. Receive signed certificate
            4. Install with `install-certificate-tool`
            5. Activate with `activate-certificate-tool`

            MD;
        }

        return [Response::text($workflow)->asAssistant()];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'server_id',
                description: 'Server ID',
                required: false,
            ),
            new Argument(
                name: 'site_id',
                description: 'Site ID',
                required: false,
            ),
            new Argument(
                name: 'certificate_type',
                description: 'Certificate type: letsencrypt, custom (default: letsencrypt)',
                required: false,
            ),
        ];
    }
}
