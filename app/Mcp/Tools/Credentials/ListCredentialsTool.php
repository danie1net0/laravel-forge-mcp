<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Credentials;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use App\Integrations\Forge\Data\Credentials\CredentialData;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class ListCredentialsTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
    List all server provider credentials configured in your Laravel Forge account.

    Credentials store authentication information for cloud providers like:
    - DigitalOcean
    - AWS
    - Linode
    - Vultr
    - Custom VPS providers

    Returns a list of credentials with:
    - Credential ID
    - Credential name
    - Provider type

    **Note**: This is a read-only operation. Actual credential values (API tokens, keys)
    are never exposed for security reasons.

    **Example Response:**
    ```json
    {
        "success": true,
        "credentials": [
            {
                "id": 1,
                "name": "DigitalOcean Production",
                "type": "ocean2"
            },
            {
                "id": 2,
                "name": "AWS Account",
                "type": "aws"
            }
        ],
        "count": 2
    }
    ```

    Use these credential IDs when creating new servers to specify which cloud provider
    account should be used.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        try {
            $credentials = $client->credentials()->list()->credentials;

            $formatted = array_map(fn (CredentialData $credential): array => [
                'id' => $credential->id,
                'name' => $credential->name,
                'type' => $credential->type,
            ], $credentials);

            return Response::text(json_encode([
                'success' => true,
                'credentials' => $formatted,
                'count' => count($formatted),
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve credentials.',
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
