<?php

declare(strict_types=1);

use App\Integrations\Forge\Data\Deployments\DeploymentData;
use App\Integrations\Forge\Data\Jobs\{JobData};
use App\Integrations\Forge\Data\Sites\{CreateSiteData, SiteData};
use App\Integrations\Forge\Data\Daemons\{DaemonData};
use App\Integrations\Forge\Data\SSHKeys\{SSHKeyData};
use App\Integrations\Forge\Data\Servers\{CreateServerData, EventData, ServerData};
use App\Integrations\Forge\Data\Workers\{CreateWorkerData, WorkerData};
use App\Integrations\Forge\Data\Firewall\{FirewallRuleData};
use App\Integrations\Forge\Data\Monitors\{MonitorData};
use App\Integrations\Forge\Data\Webhooks\{WebhookData};
use App\Integrations\Forge\Data\Databases\{CreateDatabaseData, DatabaseData};
use App\Integrations\Forge\Data\Certificates\CertificateData;

describe('ServerData', function (): void {
    it('creates from array with snake_case keys', function (): void {
        $data = ServerData::from([
            'id' => 1,
            'credential_id' => 1,
            'name' => 'test-server',
            'type' => 'app',
            'provider' => 'ocean2',
            'identifier' => 'test-1',
            'size' => '1gb',
            'region' => 'nyc1',
            'ubuntu_version' => '22.04',
            'db_status' => 'installed',
            'redis_status' => 'installed',
            'php_version' => '8.2',
            'php_cli_version' => '8.2',
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
        ]);

        expect($data->id)->toBe(1);
        expect($data->name)->toBe('test-server');
        expect($data->ipAddress)->toBe('192.168.1.1');
        expect($data->privateIpAddress)->toBe('10.0.0.1');
        expect($data->isReady)->toBeTrue();
        expect($data->opcacheStatus)->toBe('enabled');
        expect($data->revoked)->toBeFalse();
    });

    it('handles optional fields', function (): void {
        $data = ServerData::from([
            'id' => 1,
            'credential_id' => null,
            'name' => 'minimal-server',
            'type' => 'app',
            'provider' => 'ocean2',
            'identifier' => null,
            'size' => '1gb',
            'region' => 'nyc1',
            'ubuntu_version' => '22.04',
            'db_status' => null,
            'redis_status' => null,
            'php_version' => '8.2',
            'php_cli_version' => '8.2',
            'opcache_status' => 'disabled',
            'database_type' => 'mysql8',
            'ip_address' => '192.168.1.1',
            'ssh_port' => 22,
            'private_ip_address' => '10.0.0.1',
            'local_public_key' => 'ssh-rsa AAAA...',
            'blackfire_status' => null,
            'papertrail_status' => null,
            'revoked' => false,
            'created_at' => '2024-01-01T00:00:00Z',
            'is_ready' => false,
            'tags' => [],
            'network' => [],
        ]);

        expect($data->id)->toBe(1);
        expect($data->name)->toBe('minimal-server');
        expect($data->credentialId)->toBeNull();
    });
});

describe('CreateServerData', function (): void {
    it('creates with required fields', function (): void {
        $data = CreateServerData::from([
            'name' => 'new-server',
            'provider' => 'ocean2',
            'type' => 'app',
            'ubuntu_version' => '24.04',
            'php_version' => 'php82',
        ]);

        expect($data->name)->toBe('new-server');
        expect($data->provider)->toBe('ocean2');
        expect($data->type)->toBe('app');
        expect($data->ubuntuVersion)->toBe('24.04');
    });
});

describe('SiteData', function (): void {
    it('creates from array', function (): void {
        $data = SiteData::from([
            'id' => 1,
            'server_id' => 1,
            'name' => 'example.com',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
            'url' => 'http://example.com',
            'user' => 'forge',
            'https' => false,
            'web_directory' => '/public',
            'root_directory' => '/home/forge/example.com',
            'aliases' => [],
            'php_version' => 'php82',
            'quick_deploy' => true,
            'wildcards' => false,
            'app_type' => 'php',
            'tags' => [],
        ]);

        expect($data->id)->toBe(1);
        expect($data->name)->toBe('example.com');
        expect($data->quickDeploy)->toBeTrue();
        expect($data->user)->toBe('forge');
        expect($data->wildcards)->toBeFalse();
        expect($data->https)->toBeFalse();
    });

    it('handles repository fields', function (): void {
        $data = SiteData::from([
            'id' => 1,
            'server_id' => 1,
            'name' => 'example.com',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
            'user' => 'forge',
            'https' => true,
            'web_directory' => '/public',
            'root_directory' => '/home/forge/example.com',
            'aliases' => [],
            'php_version' => 'php82',
            'quick_deploy' => false,
            'wildcards' => false,
            'repository' => ['provider' => 'github', 'url' => 'user/repo', 'branch' => 'main', 'status' => 'installed'],
            'app_type' => 'php',
            'tags' => [],
        ]);

        expect($data->repository)->toBe(['provider' => 'github', 'url' => 'user/repo', 'branch' => 'main', 'status' => 'installed']);
        expect($data->https)->toBeTrue();
    });
});

describe('CreateSiteData', function (): void {
    it('creates with required fields', function (): void {
        $data = CreateSiteData::from([
            'domain' => 'example.com',
            'project_type' => 'php',
        ]);

        expect($data->domain)->toBe('example.com');
        expect($data->projectType)->toBe('php');
    });

    it('includes optional directory', function (): void {
        $data = CreateSiteData::from([
            'domain' => 'example.com',
            'project_type' => 'php',
            'directory' => '/public',
        ]);

        expect($data->directory)->toBe('/public');
    });
});

describe('DatabaseData', function (): void {
    it('creates from array', function (): void {
        $data = DatabaseData::from([
            'id' => 1,
            'server_id' => 1,
            'name' => 'forge',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data->id)->toBe(1);
        expect($data->name)->toBe('forge');
        expect($data->status)->toBe('installed');
    });
});

describe('CreateDatabaseData', function (): void {
    it('creates with name only', function (): void {
        $data = CreateDatabaseData::from(['name' => 'mydb']);

        expect($data->name)->toBe('mydb');
    });

    it('creates with user and password', function (): void {
        $data = CreateDatabaseData::from([
            'name' => 'mydb',
            'user' => 'myuser',
            'password' => 'secret123',
        ]);

        expect($data->name)->toBe('mydb');
        expect($data->user)->toBe('myuser');
        expect($data->password)->toBe('secret123');
    });
});

describe('CertificateData', function (): void {
    it('creates from array', function (): void {
        $data = CertificateData::from([
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
        ]);

        expect($data->id)->toBe(1);
        expect($data->domain)->toBe('example.com');
        expect($data->type)->toBe('letsencrypt');
        expect($data->active)->toBeTrue();
        expect($data->status)->toBe('installed');
    });
});

describe('WorkerData', function (): void {
    it('creates from array', function (): void {
        $data = WorkerData::from([
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
        ]);

        expect($data->id)->toBe(1);
        expect($data->connection)->toBe('redis');
        expect($data->timeout)->toBe(60);
        expect($data->command)->toBe('php artisan queue:work');
        expect($data->environment)->toBe('production');
    });
});

describe('CreateWorkerData', function (): void {
    it('creates with required fields', function (): void {
        $data = CreateWorkerData::from([
            'connection' => 'redis',
            'queue' => 'default',
        ]);

        expect($data->connection)->toBe('redis');
        expect($data->queue)->toBe('default');
    });

    it('includes optional fields', function (): void {
        $data = CreateWorkerData::from([
            'connection' => 'redis',
            'queue' => 'high,default,low',
            'timeout' => 300,
            'sleep' => 5,
            'tries' => 3,
            'daemon' => true,
            'force' => true,
        ]);

        expect($data->timeout)->toBe(300);
        expect($data->tries)->toBe(3);
    });
});

describe('JobData', function (): void {
    it('creates from array', function (): void {
        $data = JobData::from([
            'id' => 1,
            'server_id' => 1,
            'command' => 'php artisan schedule:run',
            'user' => 'forge',
            'frequency' => 'minutely',
            'cron' => '* * * * *',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data->id)->toBe(1);
        expect($data->command)->toBe('php artisan schedule:run');
        expect($data->frequency)->toBe('minutely');
    });
});

describe('DaemonData', function (): void {
    it('creates from array', function (): void {
        $data = DaemonData::from([
            'id' => 1,
            'server_id' => 1,
            'command' => 'node server.js',
            'user' => 'forge',
            'directory' => '/home/forge/app',
            'status' => 'running',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data->id)->toBe(1);
        expect($data->command)->toBe('node server.js');
        expect($data->directory)->toBe('/home/forge/app');
    });
});

describe('FirewallRuleData', function (): void {
    it('creates from array', function (): void {
        $data = FirewallRuleData::from([
            'id' => 1,
            'server_id' => 1,
            'name' => 'SSH',
            'port' => 22,
            'ip_address' => '192.168.1.1',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data->id)->toBe(1);
        expect($data->name)->toBe('SSH');
        expect($data->port)->toBe(22);
        expect($data->ipAddress)->toBe('192.168.1.1');
    });

    it('handles null ip_address', function (): void {
        $data = FirewallRuleData::from([
            'id' => 1,
            'server_id' => 1,
            'name' => 'HTTP',
            'port' => 80,
            'ip_address' => null,
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data->ipAddress)->toBeNull();
    });
});

describe('MonitorData', function (): void {
    it('creates from array', function (): void {
        $data = MonitorData::from([
            'id' => 1,
            'server_id' => 1,
            'type' => 'disk',
            'status' => 'installed',
            'state' => 'ok',
            'operator' => '>=',
            'threshold' => 90,
            'minutes' => 5,
            'state_changed_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data->id)->toBe(1);
        expect($data->type)->toBe('disk');
        expect($data->threshold)->toBe(90);
        expect($data->stateChangedAt)->toBe('2024-01-01T00:00:00Z');
    });
});

describe('SSHKeyData', function (): void {
    it('creates from array', function (): void {
        $data = SSHKeyData::from([
            'id' => 1,
            'server_id' => 1,
            'name' => 'my-key',
            'status' => 'installed',
            'username' => 'forge',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data->id)->toBe(1);
        expect($data->name)->toBe('my-key');
        expect($data->serverId)->toBe(1);
        expect($data->username)->toBe('forge');
    });
});

describe('WebhookData', function (): void {
    it('creates from array', function (): void {
        $data = WebhookData::from([
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'url' => 'https://example.com/webhook',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data->id)->toBe(1);
        expect($data->url)->toBe('https://example.com/webhook');
        expect($data->serverId)->toBe(1);
        expect($data->siteId)->toBe(1);
    });
});

describe('DeploymentData', function (): void {
    it('creates from array', function (): void {
        $data = DeploymentData::from([
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'type' => 1,
            'commit_hash' => 'abc123',
            'commit_author' => 'John Doe',
            'commit_message' => 'Update feature',
            'status' => 'finished',
            'started_at' => '2024-01-01T00:00:00Z',
            'ended_at' => '2024-01-01T00:01:00Z',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data->id)->toBe(1);
        expect($data->commitHash)->toBe('abc123');
        expect($data->status)->toBe('finished');
    });
});

describe('EventData', function (): void {
    it('creates from array', function (): void {
        $data = EventData::from([
            'id' => 1,
            'server_id' => 1,
            'run_as' => 'root',
            'description' => 'Server rebooted',
            'status' => 'success',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data->id)->toBe(1);
        expect($data->description)->toBe('Server rebooted');
        expect($data->runAs)->toBe('root');
    });
});

describe('Data Objects Structure Validation', function (): void {
    it('validates all Data objects exist in expected directories', function (): void {
        $dataPath = app_path('Integrations/Forge/Data');
        $dataFiles = collect(File::allFiles($dataPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Data.php'))
            ->values();

        expect($dataFiles->count())->toBeGreaterThan(58);

        foreach ($dataFiles as $file) {
            $relativePath = str_replace(
                [app_path('Integrations/Forge/Data/'), '.php', '/'],
                ['', '', '\\'],
                $file->getPathname()
            );
            $className = "App\\Integrations\\Forge\\Data\\{$relativePath}";

            expect(class_exists($className))->toBeTrue("Class {$className} should exist");
        }
    });

    it('validates all Data objects have SnakeCaseMapper', function (): void {
        $dataPath = app_path('Integrations/Forge/Data');
        $dataFiles = collect(File::allFiles($dataPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Data.php'))
            ->filter(fn ($file) => ! str_contains($file->getFilename(), 'Collection'))
            ->values();

        $failures = [];

        foreach ($dataFiles as $file) {
            $content = file_get_contents($file->getPathname());

            if (! str_contains($content, 'SnakeCaseMapper')) {
                $failures[] = $file->getFilename();
            }
        }

        expect($failures)->toBeEmpty(
            "The following DTOs are missing SnakeCaseMapper:\n" . implode("\n", $failures)
        );
    });
});
