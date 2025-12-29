<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Resource;

class QueueWorkerGuideResource extends Resource
{
    protected string $uri = 'forge://guides/queue-workers';

    protected string $name = 'Queue Worker Management Guide';

    protected string $description = 'Complete guide for configuring and managing Laravel queue workers on Forge';

    protected string $mimeType = 'text/markdown';

    public function handle(Request $request): Response
    {
        $content = <<<'MD'
        # Queue Worker Management Guide for Laravel Forge

        ## Overview

        Queue workers process jobs asynchronously, improving application responsiveness.
        Forge provides first-class support for managing Laravel queue workers.

        ## Queue Connections

        ### Available Connections

        | Driver | Use Case | Performance |
        |--------|----------|-------------|
        | **redis** | Recommended for most apps | Excellent |
        | **database** | Simple setup, no Redis needed | Good |
        | **sqs** | AWS integration, massive scale | Excellent |
        | **beanstalkd** | Dedicated queue server | Excellent |
        | **sync** | Development/testing only | N/A |

        ### Redis Queue (Recommended)

        **.env Configuration:**
        ```env
        QUEUE_CONNECTION=redis
        REDIS_HOST=127.0.0.1
        REDIS_PASSWORD=null
        REDIS_PORT=6379
        ```

        ### Database Queue

        **.env Configuration:**
        ```env
        QUEUE_CONNECTION=database
        ```

        **Create migration:**
        ```bash
        php artisan queue:table
        php artisan migrate
        ```

        ## Creating Workers in Forge

        ### Via Forge Dashboard

        1. Server → Workers
        2. Click "Add Worker"
        3. Configure:
           - Connection: redis
           - Queue: default
           - Timeout: 60
           - Sleep: 3
           - Processes: 2

        ### Via MCP Tools

        ```
        Use create-worker-tool with:
        - server_id: your server ID
        - site_id: your site ID
        - connection: redis
        - queue: default
        - timeout: 60
        - sleep: 3
        - processes: 2
        ```

        ## Worker Configuration Options

        ### Basic Options

        | Option | Description | Recommended |
        |--------|-------------|-------------|
        | **connection** | Queue driver to use | redis |
        | **queue** | Queue name(s) to process | default |
        | **timeout** | Max job execution time (seconds) | 60-300 |
        | **sleep** | Seconds to wait when no jobs | 3 |
        | **processes** | Number of parallel workers | 2-4 |
        | **tries** | Max job retry attempts | 3 |

        ### Queue Priority

        Process multiple queues with priority:

        ```
        high,default,low
        ```

        This processes `high` queue first, then `default`, then `low`.

        ### Memory and Process Limits

        | Option | Description |
        |--------|-------------|
        | **max_jobs** | Stop after processing N jobs |
        | **max_time** | Stop after N seconds |
        | **memory** | Stop when memory exceeds (MB) |

        ## Worker Patterns

        ### Pattern 1: Single Default Worker

        For simple applications:

        ```
        Connection: redis
        Queue: default
        Processes: 2
        Timeout: 60
        ```

        ### Pattern 2: Priority Queues

        For applications with urgent jobs:

        **Worker 1 (High Priority):**
        ```
        Queue: high,default
        Processes: 2
        Timeout: 30
        ```

        **Worker 2 (Background):**
        ```
        Queue: low,emails
        Processes: 1
        Timeout: 300
        ```

        ### Pattern 3: Dedicated Workers

        For specific job types:

        **Email Worker:**
        ```
        Queue: emails
        Processes: 1
        Timeout: 120
        ```

        **Report Worker:**
        ```
        Queue: reports
        Processes: 1
        Timeout: 600
        Memory: 512
        ```

        **Notification Worker:**
        ```
        Queue: notifications
        Processes: 3
        Timeout: 30
        ```

        ### Pattern 4: Horizon (Advanced)

        For complex queue management:

        1. Install Horizon:
           ```bash
           composer require laravel/horizon
           php artisan horizon:install
           ```

        2. Use daemon instead of workers:
           ```
           Use create-daemon-tool with:
           - command: php artisan horizon
           - directory: /home/forge/site.com
           ```

        3. Configure in `config/horizon.php`

        ## Dispatching Jobs

        ### Basic Dispatch

        ```php
        // Dispatch to default queue
        ProcessOrder::dispatch($order);

        // Dispatch to specific queue
        SendEmail::dispatch($user)->onQueue('emails');

        // Dispatch with delay
        SendReminder::dispatch($user)->delay(now()->addMinutes(10));
        ```

        ### Job Class Example

        ```php
        class ProcessOrder implements ShouldQueue
        {
            use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

            public int $tries = 3;
            public int $timeout = 120;
            public int $maxExceptions = 2;

            public function __construct(
                public Order $order
            ) {}

            public function handle(): void
            {
                // Process the order
            }

            public function failed(Throwable $exception): void
            {
                // Handle failure
            }
        }
        ```

        ### Job Middleware

        ```php
        public function middleware(): array
        {
            return [
                new RateLimited('orders'),
                new WithoutOverlapping($this->order->id),
            ];
        }
        ```

        ## Monitoring Workers

        ### Check Worker Status

        Using MCP:
        ```
        Use list-workers-tool with server_id and site_id
        Use get-worker-tool for detailed status
        ```

        ### Worker Health Indicators

        | Status | Meaning |
        |--------|---------|
        | **running** | Worker is active |
        | **paused** | Worker is paused |
        | **stopped** | Worker is not running |

        ### Log Locations

        ```
        /home/forge/.forge/worker-*.log
        storage/logs/laravel.log
        ```

        ### Monitor Queue Size

        Using Tinker or scheduled task:
        ```php
        Queue::size('default')
        Queue::size('emails')
        ```

        Set up alerts if queue grows too large.

        ## Troubleshooting

        ### Issue: Jobs Not Processing

        **Check 1: Worker Running?**
        ```
        Use list-workers-tool to see worker status
        ```

        **Check 2: Correct Queue?**
        ```php
        // In job
        public $queue = 'emails';

        // In worker config
        Queue: emails (not default)
        ```

        **Check 3: Connection Working?**
        ```bash
        redis-cli ping
        # Should return: PONG
        ```

        ### Issue: Jobs Failing

        **View Failed Jobs:**
        ```bash
        php artisan queue:failed
        ```

        **Retry Failed Jobs:**
        ```bash
        php artisan queue:retry all
        php artisan queue:retry [job-id]
        ```

        **Clear Failed Jobs:**
        ```bash
        php artisan queue:flush
        ```

        **Check 1: Timeout Too Short?**
        Increase timeout if jobs need more time.

        **Check 2: Memory Limit?**
        Increase memory or optimize job.

        **Check 3: Exception in Job?**
        Check `storage/logs/laravel.log` for errors.

        ### Issue: Workers Keep Dying

        **Solution 1: Restart Workers After Deploy**
        Add to deployment script:
        ```bash
        php artisan queue:restart
        ```

        **Solution 2: Memory Issues**
        Set memory limit:
        ```
        Memory: 256
        ```

        **Solution 3: Use Supervisor Settings**
        Workers auto-restart by default in Forge.

        ### Issue: Duplicate Job Processing

        **Cause:** Worker crashed mid-job, job retried.

        **Solution:** Make jobs idempotent or use unique IDs:
        ```php
        public function uniqueId(): string
        {
            return $this->order->id;
        }
        ```

        ## Best Practices

        ### 1. Job Design

        ✅ **DO:**
        - Keep jobs small and focused
        - Make jobs idempotent (safe to retry)
        - Use meaningful queue names
        - Set appropriate timeouts

        ❌ **DON'T:**
        - Process large datasets in single job
        - Store sensitive data in job payload
        - Use sync driver in production

        ### 2. Queue Configuration

        ✅ **DO:**
        - Use Redis for production
        - Separate queues by priority/type
        - Monitor queue sizes
        - Set up failed job handling

        ❌ **DON'T:**
        - Process everything on `default`
        - Ignore failed jobs
        - Skip `queue:restart` in deploy

        ### 3. Error Handling

        ```php
        public function handle(): void
        {
            try {
                // Main logic
            } catch (RecoverableException $e) {
                // Release back to queue
                $this->release(60);
            } catch (FatalException $e) {
                // Fail permanently
                $this->fail($e);
            }
        }
        ```

        ### 4. Rate Limiting

        ```php
        // In job
        public function middleware(): array
        {
            return [
                (new RateLimited('api'))->allow(60)->everyMinute(),
            ];
        }
        ```

        ### 5. Job Batching

        For processing many items:

        ```php
        $batch = Bus::batch([
            new ProcessItem($item1),
            new ProcessItem($item2),
            new ProcessItem($item3),
        ])->then(function (Batch $batch) {
            // All jobs completed
        })->catch(function (Batch $batch, Throwable $e) {
            // First failure
        })->finally(function (Batch $batch) {
            // Batch finished
        })->dispatch();
        ```

        ## Scaling Workers

        ### Vertical Scaling

        Increase processes per worker:
        ```
        Processes: 4 → 8
        ```

        ### Horizontal Scaling

        Add more workers for different queues:
        ```
        Worker 1: high (4 processes)
        Worker 2: default (4 processes)
        Worker 3: low (2 processes)
        ```

        ### Auto-Scaling with Horizon

        ```php
        'supervisor-1' => [
            'minProcesses' => 1,
            'maxProcesses' => 10,
        ],
        ```

        ## Deployment Considerations

        ### Deployment Script

        Add to your deployment script:
        ```bash
        php artisan queue:restart
        ```

        This gracefully restarts workers after code changes.

        ### Zero-Downtime Deployment

        Workers finish current job before restarting:
        1. New code deployed
        2. `queue:restart` signals workers
        3. Workers finish current jobs
        4. Workers restart with new code

        ### Maintenance Mode

        Workers continue during maintenance by default.

        To pause:
        ```bash
        php artisan down --with-secret=[bypass-token]
        ```

        Workers will stop picking up new jobs.

        ## Related Tools

        - `list-workers-tool` - List all workers
        - `get-worker-tool` - Get worker details
        - `create-worker-tool` - Create new worker
        - `restart-worker-tool` - Restart a worker
        - `delete-worker-tool` - Remove a worker
        - `get-worker-output-tool` - View worker logs
        - `install-horizon-tool` - Install Horizon daemon

        MD;

        return Response::text($content);
    }
}
