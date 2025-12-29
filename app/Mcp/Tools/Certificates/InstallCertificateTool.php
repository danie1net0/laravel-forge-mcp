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
class InstallCertificateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Install/activate an SSL certificate on a site.

        Activates a certificate that has already been created or obtained.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `site_id`: The unique ID of the site
        - `certificate_id`: The unique ID of the certificate to install

        **Optional Parameters:**
        - `add_san_redirect`: Add redirect for SAN domains (default: false)

        Returns success when certificate is activated.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'site_id' => ['required', 'integer', 'min:1'],
            'certificate_id' => ['required', 'integer', 'min:1'],
            'add_san_redirect' => ['nullable', 'boolean'],
        ]);

        $serverId = $request->integer('server_id');
        $siteId = $request->integer('site_id');
        $certificateId = $request->integer('certificate_id');
        $data = [];

        if ($request->has('add_san_redirect')) {
            $data['add_san_redirect'] = $request->boolean('add_san_redirect');
        }

        try {
            $client->certificates()->activate($serverId, $siteId, $certificateId);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Certificate installed and activated successfully',
                'certificate_id' => $certificateId,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to install certificate.',
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
                ->description('The unique ID of the certificate to install')
                ->min(1)
                ->required(),
            'add_san_redirect' => $schema->boolean()
                ->description('Add redirect for SAN domains'),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
