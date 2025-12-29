<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class SetupNewServerPrompt extends Prompt
{
    protected string $name = 'setup-new-server';

    protected string $description = 'Complete server provisioning workflow with security hardening and monitoring';

    public function handle(Request $request): array
    {
        $provider = $request->string('provider', '');
        $region = $request->string('region', '');
        $serverType = $request->string('server_type', 'app');
        $phpVersion = $request->string('php_version', 'php84');
        $database = $request->string('database', 'mysql8');

        $workflow = "# Complete Server Setup Workflow\n\n";

        if ($provider->isEmpty()) {
            $workflow .= <<<'MD'
            ## Step 1: Choose Provider

            1. Use `list-credentials-tool` to see available provider credentials
            2. Available providers: DigitalOcean, Linode, Vultr, AWS, Hetzner, Custom VPS
            3. Ask user which provider to use

            MD;
        }

        if ($region->isEmpty()) {
            $workflow .= <<<'MD'
            ## Step 2: Choose Region

            1. Use `list-regions-tool` with the chosen provider to see available regions
            2. Recommend region closest to target audience
            3. Consider latency and data residency requirements

            MD;
        }

        $workflow .= <<<'MD'
        ## Step 3: Server Configuration

        1. Use `create-server-tool` with parameters:
           - provider: chosen provider
           - region: chosen region
           - size: recommend appropriate size (1GB for small apps, 2GB+ for production)
           - php_version: php84 (recommended)
           - database: mysql8 or postgres16

        2. Wait for server provisioning (usually 5-10 minutes)

        ## Step 4: Verify Server Status

        3. Use `get-server-tool` to check server status
        4. Server should show status: "installed"
        5. Note the server's IP address for DNS configuration

        ## Step 5: Security Hardening

        6. Use `create-firewall-rule-tool` to restrict SSH access:
           - Allow SSH only from known IPs
           - Port 22, protocol TCP

        7. Use `create-ssh-key-tool` to add authorized SSH keys:
           - Add team members' public keys

        ## Step 6: Setup Monitoring

        8. Use `create-monitor-tool` to add uptime monitoring:
           - Type: URL
           - Interval: 1 minute recommended for production

        ## Step 7: Verify Installation

        9. Use `list-services-tool` to verify running services:
           - PHP-FPM should be active
           - Nginx should be active
           - Database should be active

        ## Post-Setup Recommendations

        - Configure backup storage (AWS S3, DigitalOcean Spaces, etc.)
        - Setup New Relic or Blackfire for APM
        - Configure logrotate if needed
        - Consider setting up a queue worker daemon

        MD;

        $workflow .= "\n## Server Type: {$serverType->value()}\n";
        $workflow .= "## PHP Version: {$phpVersion->value()}\n";
        $workflow .= "## Database: {$database->value()}\n";

        return [Response::text($workflow)->asAssistant()];
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'provider',
                description: 'Cloud provider (digitalocean, linode, vultr, aws, hetzner, custom)',
                required: false,
            ),
            new Argument(
                name: 'region',
                description: 'Server region (e.g., nyc1, sfo1, lon1)',
                required: false,
            ),
            new Argument(
                name: 'server_type',
                description: 'Server type: app, web, database, worker, cache, loadbalancer (default: app)',
                required: false,
            ),
            new Argument(
                name: 'php_version',
                description: 'PHP version: php74, php80, php81, php82, php83, php84 (default: php84)',
                required: false,
            ),
            new Argument(
                name: 'database',
                description: 'Database type: mysql8, mariadb, postgres13, postgres14, postgres15, postgres16 (default: mysql8)',
                required: false,
            ),
        ];
    }
}
