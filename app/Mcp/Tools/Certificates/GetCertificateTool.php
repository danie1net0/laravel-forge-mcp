<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Certificates;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly]
#[IsIdempotent]
class GetCertificateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get detailed information about a specific SSL certificate for a domain on a Laravel Forge server.

        Returns complete certificate information including:
        - Domain ID
        - Domain names
        - Type (Let's Encrypt or Custom)
        - Status (active, installing, failed)
        - Request status
        - Activation status
        - Created date

        This is a read-only operation and will not modify the certificate.

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
            $certificate = $client->certificates()->get($serverId, $siteId, $domainId);

            return Response::text(json_encode([
                'success' => true,
                'certificate' => [
                    'id' => $certificate->id,
                    'domain' => $certificate->domain,
                    'type' => $certificate->type,
                    'status' => $certificate->status,
                    'active' => $certificate->active,
                    'expires_at' => $certificate->expiresAt,
                    'request_status' => $certificate->requestStatus,
                    'activation_error' => $certificate->activationError,
                    'created_at' => $certificate->createdAt,
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $exception) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $exception->getMessage(),
                'message' => 'Failed to retrieve certificate. Please verify the IDs are correct.',
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
