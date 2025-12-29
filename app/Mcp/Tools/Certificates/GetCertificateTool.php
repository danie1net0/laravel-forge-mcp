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
        Get detailed information about a specific SSL certificate on a Laravel Forge server.

        Returns complete certificate information including:
        - Certificate ID
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
        - `certificate_id`: The unique ID of the certificate
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
            $certificate = $client->certificates()->get($serverId, $siteId, $certificateId);

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
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
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
            'certificate_id' => $schema->integer()
                ->description('The unique ID of the certificate')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
