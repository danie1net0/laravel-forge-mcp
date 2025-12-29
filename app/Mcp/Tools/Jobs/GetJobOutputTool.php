<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Jobs;

use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;

class GetJobOutputTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get the output of a scheduled job execution.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `job_id`: The unique ID of the scheduled job

        Returns the console output from the last job execution.
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
            $output = $client->jobs()->getOutput($serverId, $jobId);

            return Response::text(json_encode([
                'success' => true,
                'server_id' => $serverId,
                'job_id' => $jobId,
                'output' => $output,
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
