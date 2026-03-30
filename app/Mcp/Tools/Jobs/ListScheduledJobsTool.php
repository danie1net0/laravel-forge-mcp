<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Jobs;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use App\Integrations\Forge\Data\Jobs\JobData;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListScheduledJobsTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        List all scheduled jobs (cron jobs) on a specific Laravel Forge server.

        Returns a list of scheduled jobs including:
        - Job ID
        - Command
        - User
        - Frequency/Expression
        - Status
        - Created date

        This is a read-only operation and will not modify any jobs.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $cursor = $request->has('cursor') ? $request->string('cursor')->value() : null;
        $pageSize = $request->has('page_size') ? $request->integer('page_size') : 30;

        try {
            $jobs = $client->jobs()->list($serverId, $cursor, $pageSize)->jobs;

            $formatted = array_map(fn (JobData $job): array => [
                'id' => $job->id,
                'command' => $job->command,
                'user' => $job->user,
                'frequency' => $job->frequency,
                'cron' => $job->cron,
                'status' => $job->status,
                'created_at' => $job->createdAt,
            ], $jobs);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'count' => count($formatted),
                'jobs' => $formatted,
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
        return [
            'server_id' => $schema->integer()
                ->description('The unique ID of the Forge server')
                ->min(1)
                ->required(),
            'cursor' => $schema->string()->description('Pagination cursor for next page')->nullable(),
            'page_size' => $schema->integer()->description('Items per page (default 30)')->min(1)->max(100)->nullable(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
