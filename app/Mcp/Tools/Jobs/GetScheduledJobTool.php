<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Jobs;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\{IsIdempotent, IsReadOnly};

#[IsReadOnly]
#[IsIdempotent]
class GetScheduledJobTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get detailed information about a specific scheduled job (cron job) on a Laravel Forge server.

        Returns complete job information including:
        - Job ID
        - Command
        - User
        - Frequency/Cron expression
        - Status
        - Created date

        This is a read-only operation and will not modify the job.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `job_id`: The unique ID of the scheduled job
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'job_id' => ['required', 'integer', 'min:1'],
        ]);

        $serverId = $request->integer('server_id');
        $jobId = $request->integer('job_id');

        try {
            $job = $client->jobs()->get($serverId, $jobId);

            return Response::text(json_encode([
                'success' => true,
                'job' => [
                    'id' => $job->id,
                    'command' => $job->command,
                    'user' => $job->user,
                    'frequency' => $job->frequency,
                    'cron' => $job->cron,
                    'status' => $job->status,
                    'created_at' => $job->createdAt,
                ],
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to retrieve scheduled job. Please verify the server_id and job_id are correct.',
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
            'job_id' => $schema->integer()
                ->description('The unique ID of the scheduled job')
                ->min(1)
                ->required(),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
