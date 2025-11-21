<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Certificates;

use App\Services\ForgeService;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Exception;

#[IsReadOnly]
class ListCertificatesTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all SSL certificates for a specific site on a Laravel Forge server.

        Returns a list of SSL certificates including:
        - Certificate ID
        - Domain names
        - Type (Let's Encrypt or Custom)
        - Status (active, installing, failed)
        - Expiration date
        - Created date

        This is a read-only operation and will not modify any certificates.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
    MARKDOWN;

    public function handle(Request $request, ForgeService $forge): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');

        try {
            $certificates = $forge->listCertificates($serverId, $siteId);

            $formatted = array_map(fn ($cert) => [
                'id' => $cert->id,
                'domain' => $cert->domain ?? null,
                'type' => $cert->type ?? null,
                'status' => $cert->status ?? null,
                'active' => $cert->active ?? false,
                'existing' => $cert->existing ?? false,
                'created_at' => $cert->createdAt ?? null,
            ], $certificates);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'site_id' => $siteId,
                'count' => count($formatted),
                'certificates' => $formatted,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve certificates. Please verify the server_id and site_id are correct.',
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
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
