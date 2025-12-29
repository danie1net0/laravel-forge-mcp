<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Jobs;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteScheduledJobTool extends Tool
{
    protected string $description = 'Delete a scheduled job (cron). Requires server_id and job_id.';

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate(['server_id' => ['required', 'integer', 'min:1'], 'job_id' => ['required', 'integer', 'min:1']]);

        try {
            $client->jobs()->delete($request->integer('server_id'), $request->integer('job_id'));

            return Response::text(json_encode(['success' => true, 'message' => 'Scheduled job deleted'], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return ['server_id' => $schema->integer()->min(1)->required(), 'job_id' => $schema->integer()->min(1)->required()];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
