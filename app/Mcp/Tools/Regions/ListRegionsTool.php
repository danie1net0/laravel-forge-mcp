<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Regions;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};
use Laravel\Mcp\{Request, Response};

#[IsReadOnly, IsIdempotent]
class ListRegionsTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all available cloud provider regions.

        Returns a list of regions with their available server sizes for each cloud provider.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        try {
            $regions = $client->regions()->list();

            return Response::text(json_encode([
                'success' => true,
                'regions' => $regions,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
