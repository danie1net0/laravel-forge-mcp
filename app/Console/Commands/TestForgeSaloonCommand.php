<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Integrations\Forge\Data\Servers\ServerData;
use App\Integrations\Forge\ForgeClient;
use Exception;
use Illuminate\Console\Command;

class TestForgeSaloonCommand extends Command
{
    protected $signature = 'app:test-forge';

    protected $description = 'Test Saloon integration with Forge API';

    public function handle(ForgeClient $client): int
    {
        $this->info('Testing Forge API with Saloon...');
        $this->newLine();

        try {
            return $this->testConnection($client);
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->line($e->getTraceAsString());

            return self::FAILURE;
        }
    }

    private function testConnection(ForgeClient $client): int
    {
        $this->line('Fetching servers...');
        $data = $client->servers()->list();

        $this->info('Servers retrieved successfully!');
        $this->line('Total: ' . count($data->servers) . ' server(s)');
        $this->newLine();

        $server = $data->servers[0] ?? null;

        return match (true) {
            $server === null => self::SUCCESS,
            default => $this->showServerDetails($client, $server),
        };
    }

    private function showServerDetails(ForgeClient $client, ServerData $server): int
    {
        $this->line('First server:');
        $this->line("  ID: {$server->id}");
        $this->line("  Name: {$server->name}");
        $this->line("  IP: {$server->ipAddress}");
        $this->line("  PHP: {$server->phpVersion}");
        $this->line("  Ubuntu: {$server->ubuntuVersion}");
        $this->line('  Ready: ' . ($server->isReady ? 'Yes' : 'No'));
        $this->newLine();

        $this->line('Fetching server details...');
        $serverDetails = $client->servers()->get($server->id);
        $this->info("Server '{$serverDetails->name}' details retrieved!");

        return self::SUCCESS;
    }
}
