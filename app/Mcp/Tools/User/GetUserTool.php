<?php

declare(strict_types=1);

namespace App\Mcp\Tools\User;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly, IsIdempotent]
class GetUserTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get the currently authenticated user's information.

        Returns user details including:
        - User ID and name
        - Email address
        - Connected providers (GitHub, GitLab, Bitbucket, etc.)
        - Connected cloud providers (DigitalOcean, AWS, Vultr, etc.)
        - Billing status

        This is useful for verifying API token ownership and checking account status.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        try {
            $user = $client->user()->get();

            return Response::text(json_encode([
                'success' => true,
                'user' => $user->toArray(),
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
