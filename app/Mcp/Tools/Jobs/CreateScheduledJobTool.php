<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Jobs;

use App\Integrations\Forge\Data\Jobs\CreateJobData;
use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreateScheduledJobTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Create a new scheduled job (cron job) on a Laravel Forge server.

        **Required Parameters:**
        - `server_id`: The unique ID of the Forge server
        - `command`: The command to execute (e.g., "php artisan schedule:run")
        - `frequency`: Job frequency - one of: "minutely", "hourly", "nightly", "weekly", "monthly", or "custom"

        **Optional Parameters:**
        - `user`: Unix user to run the job as (defaults to "forge")
        - `minute`: Minute (0-59) - required if frequency is "custom"
        - `hour`: Hour (0-23) - required if frequency is "custom"
        - `day`: Day of month (1-31) - required if frequency is "custom"
        - `month`: Month (1-12) - required if frequency is "custom"
        - `weekday`: Day of week (0-7, where 0 and 7 are Sunday) - required if frequency is "custom"

        **Examples:**
        - Minutely: `* * * * *`
        - Hourly: `0 * * * *`
        - Daily at midnight: `0 0 * * *`
        - Weekly on Sunday at 2am: `0 2 * * 0`
        - Custom: Set minute, hour, day, month, weekday individually

        Returns the created scheduled job information.
    MARKDOWN;

    public function handle(Request $request, ForgeClient $client): Response
    {
        $request->validate([
            'server_id' => ['required', 'integer', 'min:1'],
            'command' => ['required', 'string'],
            'frequency' => ['required', 'string', 'in:minutely,hourly,nightly,weekly,monthly,custom'],
            'user' => ['nullable', 'string', 'max:255'],
            'minute' => ['nullable', 'string', 'max:255'],
            'hour' => ['nullable', 'string', 'max:255'],
            'day' => ['nullable', 'string', 'max:255'],
            'month' => ['nullable', 'string', 'max:255'],
            'weekday' => ['nullable', 'string', 'max:255'],
        ]);

        $serverId = $request->integer('server_id');
        $frequency = $request->string('frequency')->value();

        $data = new CreateJobData(
            command: $request->string('command')->value(),
            frequency: $frequency,
            user: $request->has('user') ? $request->string('user')->value() : 'forge',
            minute: $frequency === 'custom' ? $request->string('minute', '*')->value() : null,
            hour: $frequency === 'custom' ? $request->string('hour', '*')->value() : null,
            day: $frequency === 'custom' ? $request->string('day', '*')->value() : null,
            month: $frequency === 'custom' ? $request->string('month', '*')->value() : null,
            weekday: $frequency === 'custom' ? $request->string('weekday', '*')->value() : null,
        );

        try {
            $job = $client->jobs()->create($serverId, $data);

            return Response::text(json_encode([
                'success' => true,
                'message' => 'Scheduled job created successfully',
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
                'message' => 'Failed to create scheduled job. Please check the parameters and try again.',
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
            'command' => $schema->string()
                ->description('The command to execute (e.g., "php artisan schedule:run")')
                ->required(),
            'frequency' => $schema->string()
                ->description('Job frequency: minutely, hourly, nightly, weekly, monthly, or custom')
                ->enum(['minutely', 'hourly', 'nightly', 'weekly', 'monthly', 'custom'])
                ->required(),
            'user' => $schema->string()
                ->description('Unix user to run the job as (defaults to "forge")'),
            'minute' => $schema->string()
                ->description('Minute (0-59 or *) - required if frequency is "custom"'),
            'hour' => $schema->string()
                ->description('Hour (0-23 or *) - required if frequency is "custom"'),
            'day' => $schema->string()
                ->description('Day of month (1-31 or *) - required if frequency is "custom"'),
            'month' => $schema->string()
                ->description('Month (1-12 or *) - required if frequency is "custom"'),
            'weekday' => $schema->string()
                ->description('Day of week (0-7 or *, where 0 and 7 are Sunday) - required if frequency is "custom"'),
        ];
    }

    public function shouldRegister(): bool
    {
        return config('services.forge.api_token') !== null;
    }
}
