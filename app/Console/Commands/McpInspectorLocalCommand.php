<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Laravel\Mcp\Server\Registrar;
use Symfony\Component\Process\{PhpExecutableFinder, Process};

class McpInspectorLocalCommand extends Command
{
    protected $signature = 'app:inspector-local {handle=forge}';

    protected $description = 'Start MCP Inspector with authentication disabled for local development';

    public function handle(Registrar $registrar): int
    {
        $handle = $this->argument('handle');

        return match (true) {
            ! is_string($handle) => $this->showError('Please pass a valid MCP server handle'),
            $registrar->servers() === [] => $this->showError('No MCP servers found. Please run `php artisan make:mcp-server [name]`'),
            default => $this->startInspector($registrar, $handle),
        };
    }

    private function startInspector(Registrar $registrar, string $handle): int
    {
        $this->components->info("Starting the MCP Inspector for server [{$handle}] (authentication disabled)");

        [$localServer, $route] = $this->resolveServer($registrar, $handle);

        return match (true) {
            $localServer === null && $route === null => $this->failWithAvailableServers($registrar, $handle),
            $localServer !== null => $this->runStdioInspector($handle),
            default => $this->runHttpInspector($route),
        };
    }

    private function resolveServer(Registrar $registrar, string $handle): array
    {
        $localServer = $registrar->getLocalServer($handle);
        $route = $registrar->getWebServer($handle);
        $servers = $registrar->servers();

        return match (count($servers)) {
            1 => $this->resolveOnlyServer(array_shift($servers)),
            default => [$localServer, $route],
        };
    }

    private function resolveOnlyServer(mixed $server): array
    {
        return match (true) {
            is_callable($server) => [$server, null],
            $server::class === Route::class => [null, $server],
            default => [null, null],
        };
    }

    private function runStdioInspector(string $handle): int
    {
        $artisanPath = base_path('artisan');

        $command = [
            'npx', '--yes', '@modelcontextprotocol/inspector',
            '--transport', 'stdio',
            $this->phpBinary(), $artisanPath, "mcp:start {$handle}",
        ];

        $guidance = [
            'Transport Type' => 'STDIO',
            'Command' => $this->phpBinary(),
            'Arguments' => implode(' ', [str_replace('\\', '/', $artisanPath), 'mcp:start', $handle]),
            'Authentication' => 'DISABLED (local development only)',
        ];

        return $this->executeInspector($command, ['DANGEROUSLY_OMIT_AUTH' => 'true'], $guidance);
    }

    private function runHttpInspector(Route $route): int
    {
        $serverUrl = url($route->uri());

        $env = ['DANGEROUSLY_OMIT_AUTH' => 'true'];
        $isHttps = parse_url($serverUrl, PHP_URL_SCHEME) === 'https';
        $env = $isHttps ? [...$env, 'NODE_TLS_REJECT_UNAUTHORIZED' => '0'] : $env;

        $command = [
            'npx', '--yes', '@modelcontextprotocol/inspector',
            '--transport', 'http',
            '--server-url', $serverUrl,
        ];

        $guidance = [
            'Transport Type' => 'Streamable HTTP',
            'URL' => $serverUrl,
            'Authentication' => 'DISABLED (local development only)',
            'Secure' => 'Your project must be accessible on HTTP for this to work due to how node manages SSL trust',
        ];

        return $this->executeInspector($command, $env, $guidance);
    }

    private function executeInspector(array $command, array $env, array $guidance): int
    {
        $process = new Process($command, null, $env);
        $process->setTimeout(null);

        try {
            collect($guidance)->each(fn ($value, $key) => $this->info("{$key} => {$value}"));
            $this->newLine();

            $process->mustRun(function (int|string $type, string $buffer): void {
                $this->output->write($buffer);
            });

            return static::SUCCESS;
        } catch (Exception $exception) {
            return $this->showError('Failed to start MCP Inspector: ' . $exception->getMessage());
        }
    }

    private function showError(string $message): int
    {
        $this->components->error($message);

        return static::FAILURE;
    }

    private function failWithAvailableServers(Registrar $registrar, string $handle): int
    {
        $available = Arr::map(array_keys($registrar->servers()), fn ($s): string => "[{$s}]");

        return $this->showError("MCP Server with name [{$handle}] not found. Available servers: " . Arr::join($available, ', '));
    }

    private function phpBinary(): string
    {
        return (new PhpExecutableFinder())->find(false) ?: 'php';
    }
}
