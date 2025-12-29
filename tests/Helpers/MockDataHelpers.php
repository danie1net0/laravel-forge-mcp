<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Integrations\Forge\Data\Certificates\CertificateData;
use App\Integrations\Forge\Data\Daemons\DaemonData;
use App\Integrations\Forge\Data\Databases\{DatabaseData, DatabaseUserData};
use App\Integrations\Forge\Data\Firewall\FirewallRuleData;
use App\Integrations\Forge\Data\Jobs\JobData;
use App\Integrations\Forge\Data\Monitors\MonitorData;
use App\Integrations\Forge\Data\Recipes\RecipeData;
use App\Integrations\Forge\Data\RedirectRules\RedirectRuleData;
use App\Integrations\Forge\Data\SecurityRules\SecurityRuleData;
use App\Integrations\Forge\Data\Servers\ServerData;
use App\Integrations\Forge\Data\Sites\SiteData;
use App\Integrations\Forge\Data\SSHKeys\SSHKeyData;
use App\Integrations\Forge\Data\Webhooks\WebhookData;
use App\Integrations\Forge\Data\Workers\WorkerData;

class MockDataHelpers
{
    public static function serverData(array $overrides = []): ServerData
    {
        return ServerData::from(array_merge([
            'id' => 1,
            'credential_id' => 1,
            'name' => 'test-server',
            'type' => 'app',
            'provider' => 'digitalocean',
            'identifier' => 'test-1',
            'size' => '1GB',
            'region' => 'nyc1',
            'ubuntu_version' => '22.04',
            'db_status' => 'installed',
            'redis_status' => 'installed',
            'php_version' => 'php84',
            'php_cli_version' => 'php84',
            'opcache_status' => 'enabled',
            'database_type' => 'mysql8',
            'ip_address' => '192.168.1.1',
            'ssh_port' => 22,
            'private_ip_address' => '10.0.0.1',
            'local_public_key' => 'ssh-rsa AAAA...',
            'blackfire_status' => null,
            'papertrail_status' => null,
            'revoked' => false,
            'created_at' => '2024-01-01T00:00:00Z',
            'is_ready' => true,
            'tags' => [],
            'network' => [],
        ], $overrides));
    }

    public static function siteData(array $overrides = []): SiteData
    {
        return SiteData::from(array_merge([
            'id' => 1,
            'server_id' => 1,
            'name' => 'example.com',
            'aliases' => null,
            'directory' => '/home/forge/example.com',
            'wildcards' => false,
            'status' => 'installed',
            'repository' => null,
            'repository_provider' => null,
            'repository_branch' => null,
            'repository_status' => null,
            'quick_deploy' => true,
            'deployment_status' => null,
            'project_type' => 'php',
            'app' => null,
            'app_status' => null,
            'hipchat_room' => null,
            'slack_channel' => null,
            'telegram_chat_id' => null,
            'telegram_chat_title' => null,
            'teams_webhook_url' => null,
            'discord_webhook_url' => null,
            'username' => 'forge',
            'balancing_status' => null,
            'created_at' => '2024-01-01T00:00:00Z',
            'deployment_url' => null,
            'is_secured' => false,
            'php_version' => 'php84',
            'tags' => [],
            'failure_deployment_emails' => null,
            'telegram_secret' => null,
            'web_directory' => '/public',
        ], $overrides));
    }

    public static function certificateData(array $overrides = []): CertificateData
    {
        return CertificateData::from(array_merge([
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'domain' => 'example.com',
            'request_status' => null,
            'status' => 'installed',
            'type' => 'letsencrypt',
            'active' => true,
            'expires_at' => '2025-01-01T00:00:00Z',
            'created_at' => '2024-01-01T00:00:00Z',
            'activation_error' => null,
        ], $overrides));
    }

    public static function databaseData(array $overrides = []): DatabaseData
    {
        return DatabaseData::from(array_merge([
            'id' => 1,
            'server_id' => 1,
            'name' => 'forge',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ], $overrides));
    }

    public static function databaseUserData(array $overrides = []): DatabaseUserData
    {
        return DatabaseUserData::from(array_merge([
            'id' => 1,
            'server_id' => 1,
            'name' => 'forge',
            'status' => 'installed',
            'databases' => [],
            'created_at' => '2024-01-01T00:00:00Z',
        ], $overrides));
    }

    public static function workerData(array $overrides = []): WorkerData
    {
        return WorkerData::from(array_merge([
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'connection' => 'redis',
            'command' => 'php artisan queue:work',
            'queue' => 'default',
            'timeout' => 60,
            'sleep' => 3,
            'tries' => 1,
            'environment' => 'production',
            'daemon' => 1,
            'status' => 'running',
            'created_at' => '2024-01-01T00:00:00Z',
        ], $overrides));
    }

    public static function daemonData(array $overrides = []): DaemonData
    {
        return DaemonData::from(array_merge([
            'id' => 1,
            'server_id' => 1,
            'command' => 'node server.js',
            'user' => 'forge',
            'directory' => '/home/forge/app',
            'status' => 'running',
            'created_at' => '2024-01-01T00:00:00Z',
        ], $overrides));
    }

    public static function jobData(array $overrides = []): JobData
    {
        return JobData::from(array_merge([
            'id' => 1,
            'server_id' => 1,
            'command' => 'php artisan schedule:run',
            'user' => 'forge',
            'frequency' => 'minutely',
            'cron' => '* * * * *',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ], $overrides));
    }

    public static function firewallRuleData(array $overrides = []): FirewallRuleData
    {
        return FirewallRuleData::from(array_merge([
            'id' => 1,
            'server_id' => 1,
            'name' => 'SSH',
            'port' => 22,
            'ip_address' => '192.168.1.1',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ], $overrides));
    }

    public static function monitorData(array $overrides = []): MonitorData
    {
        return MonitorData::from(array_merge([
            'id' => 1,
            'server_id' => 1,
            'type' => 'disk',
            'status' => 'installed',
            'state' => 'ok',
            'operator' => '>=',
            'threshold' => 90,
            'minutes' => 5,
            'state_changed_at' => '2024-01-01T00:00:00Z',
        ], $overrides));
    }

    public static function recipeData(array $overrides = []): RecipeData
    {
        return RecipeData::from(array_merge([
            'id' => 1,
            'key' => 'deploy-script',
            'name' => 'Deploy',
            'user' => 'forge',
            'created_at' => '2024-01-01T00:00:00Z',
        ], $overrides));
    }

    public static function sshKeyData(array $overrides = []): SSHKeyData
    {
        return SSHKeyData::from(array_merge([
            'id' => 1,
            'server_id' => 1,
            'name' => 'my-key',
            'status' => 'installed',
            'username' => 'forge',
            'created_at' => '2024-01-01T00:00:00Z',
        ], $overrides));
    }

    public static function webhookData(array $overrides = []): WebhookData
    {
        return WebhookData::from(array_merge([
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'url' => 'https://example.com/webhook',
            'created_at' => '2024-01-01T00:00:00Z',
        ], $overrides));
    }

    public static function redirectRuleData(array $overrides = []): RedirectRuleData
    {
        return RedirectRuleData::from(array_merge([
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'from' => '/old',
            'to' => '/new',
            'type' => 'redirect',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ], $overrides));
    }

    public static function securityRuleData(array $overrides = []): SecurityRuleData
    {
        return SecurityRuleData::from(array_merge([
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'name' => 'admin',
            'path' => '/admin',
            'credentials' => [],
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ], $overrides));
    }
}
