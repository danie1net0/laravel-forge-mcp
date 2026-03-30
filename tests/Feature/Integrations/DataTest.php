<?php

declare(strict_types=1);

use App\Integrations\Forge\Data\Deployments\DeploymentData;
use App\Integrations\Forge\Data\Jobs\{CreateJobData, JobCollectionData, JobData};
use App\Integrations\Forge\Data\Sites\{CreateSiteData, ExecuteSiteCommandData, InstallGitRepositoryData, SiteCollectionData, SiteCommandData, SiteData, UpdateGitRepositoryData, UpdateSiteData};
use App\Integrations\Forge\Data\Backups\{BackupConfigurationCollectionData, BackupConfigurationData, BackupData, CreateBackupConfigurationData, UpdateBackupConfigurationData};
use App\Integrations\Forge\Data\Daemons\{CreateDaemonData, DaemonCollectionData, DaemonData};
use App\Integrations\Forge\Data\SSHKeys\{CreateSSHKeyData, SSHKeyCollectionData, SSHKeyData};
use App\Integrations\Forge\Data\Servers\{CreateServerData, EventData, ServerCollectionData, ServerData};
use App\Integrations\Forge\Data\Workers\{CreateWorkerData, WorkerCollectionData, WorkerData};
use App\Integrations\Forge\Data\Firewall\{CreateFirewallRuleData, FirewallRuleCollectionData, FirewallRuleData};
use App\Integrations\Forge\Data\Monitors\{CreateMonitorData, MonitorCollectionData, MonitorData};
use App\Integrations\Forge\Data\Webhooks\{CreateWebhookData, WebhookCollectionData, WebhookData};
use App\Integrations\Forge\Data\Databases\{CreateDatabaseData, CreateDatabaseUserData, DatabaseCollectionData, DatabaseData, DatabaseUserCollectionData, DatabaseUserData, UpdateDatabaseUserData};
use App\Integrations\Forge\Data\Certificates\{CertificateCollectionData, CertificateData};
use App\Integrations\Forge\Data\RedirectRules\{CreateRedirectRuleData, RedirectRuleCollectionData, RedirectRuleData};
use App\Integrations\Forge\Data\SecurityRules\{CreateSecurityRuleData, SecurityRuleCollectionData, SecurityRuleData};
use App\Integrations\Forge\Data\NginxTemplates\{CreateNginxTemplateData, NginxTemplateCollectionData, NginxTemplateData, UpdateNginxTemplateData};
use App\Integrations\Forge\Data\Php\PhpVersionData;
use App\Integrations\Forge\Data\User\UserData;

describe('Backups', function (): void {
    it('creates BackupConfigurationData from snake_case input', function (): void {
        $data = BackupConfigurationData::from([
            'id' => 10,
            'server_id' => 5,
            'day_of_week' => 1,
            'time' => '03:00',
            'provider' => 's3',
            'provider_name' => 'Amazon S3',
            'last_backup_time' => '2024-06-15T03:00:00Z',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data)
            ->id->toBe(10)
            ->serverId->toBe(5)
            ->dayOfWeek->toBe(1)
            ->time->toBe('03:00')
            ->provider->toBe('s3')
            ->providerName->toBe('Amazon S3')
            ->lastBackupTime->toBe('2024-06-15T03:00:00Z')
            ->createdAt->toBe('2024-01-01T00:00:00Z');
    });

    it('creates BackupData from snake_case input', function (): void {
        $data = BackupData::from([
            'id' => 1,
            'server_id' => 2,
            'backup_configuration_id' => 3,
            'status' => 'finished',
            'restore_status' => 'none',
            'archive_path' => '/backups/2024-01-01.tar.gz',
            'size' => 1024000,
            'uuid' => 'abc-123-def',
            'duration' => '5 minutes',
            'last_backup_time' => null,
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->backupConfigurationId->toBe(3)
            ->status->toBe('finished')
            ->restoreStatus->toBe('none')
            ->archivePath->toBe('/backups/2024-01-01.tar.gz')
            ->size->toBe(1024000)
            ->uuid->toBe('abc-123-def')
            ->lastBackupTime->toBeNull();
    });

    it('creates CreateBackupConfigurationData from snake_case input', function (): void {
        $data = CreateBackupConfigurationData::from([
            'provider' => 's3',
            'provider_name' => 'Amazon S3',
            'day_of_week' => '1',
            'time' => '03:00',
        ]);

        expect($data)
            ->provider->toBe('s3')
            ->providerName->toBe('Amazon S3')
            ->dayOfWeek->toBe('1')
            ->time->toBe('03:00');
    });

    it('creates UpdateBackupConfigurationData with optional fields', function (): void {
        $data = UpdateBackupConfigurationData::from([
            'provider' => 'spaces',
        ]);

        expect($data)
            ->provider->toBe('spaces')
            ->providerName->toBeNull()
            ->dayOfWeek->toBeNull()
            ->time->toBeNull();
    });

    it('creates BackupConfigurationCollectionData with empty array', function (): void {
        $data = BackupConfigurationCollectionData::from([
            'backups' => [],
        ]);

        expect($data->backups)->toBeEmpty();
    });

    it('creates BackupConfigurationCollectionData via fromResponse', function (): void {
        $data = BackupConfigurationCollectionData::fromResponse([
            'backups' => [
                [
                    'id' => 1,
                    'server_id' => 1,
                    'day_of_week' => null,
                    'time' => '03:00',
                    'provider' => 's3',
                    'provider_name' => 'Amazon S3',
                    'last_backup_time' => null,
                    'created_at' => '2024-01-01T00:00:00Z',
                ],
            ],
        ]);

        expect($data->backups)
            ->toHaveCount(1)
            ->and($data->backups[0])
            ->toBeInstanceOf(BackupConfigurationData::class)
            ->id->toBe(1);
    });
});

describe('Certificates', function (): void {
    it('creates CertificateData from snake_case input', function (): void {
        $data = CertificateData::from([
            'id' => 1,
            'server_id' => 2,
            'site_id' => 3,
            'domain' => 'example.com',
            'request_status' => null,
            'status' => 'installed',
            'type' => 'letsencrypt',
            'active' => true,
            'expires_at' => '2025-06-01T00:00:00Z',
            'created_at' => '2024-01-01T00:00:00Z',
            'activation_error' => null,
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->siteId->toBe(3)
            ->domain->toBe('example.com')
            ->requestStatus->toBeNull()
            ->status->toBe('installed')
            ->type->toBe('letsencrypt')
            ->active->toBeTrue()
            ->expiresAt->toBe('2025-06-01T00:00:00Z')
            ->activationError->toBeNull();
    });

    it('creates CertificateCollectionData with empty array', function (): void {
        $data = CertificateCollectionData::from([
            'certificates' => [],
        ]);

        expect($data->certificates)->toBeEmpty();
    });

    it('creates CertificateCollectionData with items', function (): void {
        $data = CertificateCollectionData::from([
            'certificates' => [
                [
                    'id' => 1,
                    'server_id' => 1,
                    'site_id' => 1,
                    'domain' => 'example.com',
                    'request_status' => null,
                    'status' => 'installed',
                    'type' => 'letsencrypt',
                    'active' => true,
                    'expires_at' => null,
                    'created_at' => '2024-01-01T00:00:00Z',
                    'activation_error' => null,
                ],
            ],
        ]);

        expect($data->certificates)
            ->toHaveCount(1)
            ->and($data->certificates[0])
            ->toBeInstanceOf(CertificateData::class)
            ->domain->toBe('example.com');
    });
});

describe('Daemons', function (): void {
    it('creates DaemonData from snake_case input', function (): void {
        $data = DaemonData::from([
            'id' => 1,
            'server_id' => 2,
            'command' => 'node server.js',
            'user' => 'forge',
            'status' => 'running',
            'directory' => '/home/forge/app',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->command->toBe('node server.js')
            ->user->toBe('forge')
            ->status->toBe('running')
            ->directory->toBe('/home/forge/app');
    });

    it('creates CreateDaemonData from snake_case input', function (): void {
        $data = CreateDaemonData::from([
            'command' => 'php artisan horizon',
            'directory' => '/home/forge/app',
            'user' => 'forge',
            'processes' => 3,
            'startsecs' => 10,
        ]);

        expect($data)
            ->command->toBe('php artisan horizon')
            ->directory->toBe('/home/forge/app')
            ->user->toBe('forge')
            ->processes->toBe(3)
            ->startsecs->toBe(10);
    });

    it('creates CreateDaemonData with defaults', function (): void {
        $data = CreateDaemonData::from([
            'command' => 'node app.js',
            'directory' => '/home/forge/node-app',
        ]);

        expect($data)
            ->user->toBe('forge')
            ->processes->toBeNull()
            ->startsecs->toBeNull();
    });

    it('creates DaemonCollectionData with empty array', function (): void {
        $data = DaemonCollectionData::from([
            'daemons' => [],
        ]);

        expect($data->daemons)->toBeEmpty();
    });
});

describe('Databases', function (): void {
    it('creates DatabaseData from snake_case input', function (): void {
        $data = DatabaseData::from([
            'id' => 1,
            'server_id' => 2,
            'name' => 'forge_production',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->name->toBe('forge_production')
            ->status->toBe('installed');
    });

    it('creates CreateDatabaseData with optional user and password', function (): void {
        $data = CreateDatabaseData::from([
            'name' => 'mydb',
            'user' => 'dbuser',
            'password' => 'secret123',
        ]);

        expect($data)
            ->name->toBe('mydb')
            ->user->toBe('dbuser')
            ->password->toBe('secret123');
    });

    it('creates CreateDatabaseUserData from snake_case input', function (): void {
        $data = CreateDatabaseUserData::from([
            'name' => 'app_user',
            'password' => 'strongpassword',
            'databases' => [1, 2],
        ]);

        expect($data)
            ->name->toBe('app_user')
            ->password->toBe('strongpassword')
            ->databases->toBe([1, 2]);
    });

    it('creates DatabaseUserData from snake_case input', function (): void {
        $data = DatabaseUserData::from([
            'id' => 1,
            'server_id' => 2,
            'name' => 'forge',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
            'databases' => [1, 3],
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->name->toBe('forge')
            ->databases->toBe([1, 3]);
    });

    it('creates UpdateDatabaseUserData with databases array', function (): void {
        $data = UpdateDatabaseUserData::from([
            'databases' => [1, 2, 3],
        ]);

        expect($data->databases)->toBe([1, 2, 3]);
    });

    it('creates DatabaseCollectionData with items', function (): void {
        $data = DatabaseCollectionData::from([
            'databases' => [
                [
                    'id' => 1,
                    'server_id' => 1,
                    'name' => 'forge',
                    'status' => 'installed',
                    'created_at' => '2024-01-01T00:00:00Z',
                ],
            ],
        ]);

        expect($data->databases)
            ->toHaveCount(1)
            ->and($data->databases[0])
            ->toBeInstanceOf(DatabaseData::class)
            ->name->toBe('forge');
    });

    it('creates DatabaseUserCollectionData with items', function (): void {
        $data = DatabaseUserCollectionData::from([
            'users' => [
                [
                    'id' => 1,
                    'server_id' => 1,
                    'name' => 'forge',
                    'status' => 'installed',
                    'created_at' => '2024-01-01T00:00:00Z',
                    'databases' => [],
                ],
            ],
        ]);

        expect($data->users)
            ->toHaveCount(1)
            ->and($data->users[0])
            ->toBeInstanceOf(DatabaseUserData::class)
            ->name->toBe('forge');
    });
});

describe('Deployments', function (): void {
    it('creates DeploymentData from snake_case input', function (): void {
        $data = DeploymentData::from([
            'id' => 1,
            'server_id' => 2,
            'site_id' => 3,
            'type' => 1,
            'commit_hash' => 'abc123def',
            'commit_author' => 'John Doe',
            'commit_message' => 'Fix critical bug',
            'status' => 'finished',
            'started_at' => '2024-01-01T12:00:00Z',
            'ended_at' => '2024-01-01T12:01:30Z',
            'created_at' => '2024-01-01T12:00:00Z',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->siteId->toBe(3)
            ->type->toBe(1)
            ->commitHash->toBe('abc123def')
            ->commitAuthor->toBe('John Doe')
            ->commitMessage->toBe('Fix critical bug')
            ->status->toBe('finished')
            ->startedAt->toBe('2024-01-01T12:00:00Z')
            ->endedAt->toBe('2024-01-01T12:01:30Z');
    });

    it('creates DeploymentData with nullable fields', function (): void {
        $data = DeploymentData::from([
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'type' => 0,
            'commit_hash' => null,
            'commit_author' => null,
            'commit_message' => null,
            'status' => 'pending',
            'started_at' => null,
            'ended_at' => null,
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data)
            ->commitHash->toBeNull()
            ->commitAuthor->toBeNull()
            ->commitMessage->toBeNull()
            ->startedAt->toBeNull()
            ->endedAt->toBeNull();
    });
});

describe('Firewall', function (): void {
    it('creates FirewallRuleData from snake_case input', function (): void {
        $data = FirewallRuleData::from([
            'id' => 1,
            'server_id' => 2,
            'name' => 'Allow SSH',
            'port' => 22,
            'ip_address' => '10.0.0.1',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->name->toBe('Allow SSH')
            ->port->toBe(22)
            ->ipAddress->toBe('10.0.0.1')
            ->status->toBe('installed');
    });

    it('creates CreateFirewallRuleData from snake_case input', function (): void {
        $data = CreateFirewallRuleData::from([
            'name' => 'Allow HTTP',
            'port' => 80,
            'ip_address' => '0.0.0.0',
        ]);

        expect($data)
            ->name->toBe('Allow HTTP')
            ->port->toBe(80)
            ->ipAddress->toBe('0.0.0.0');
    });

    it('creates CreateFirewallRuleData with string port', function (): void {
        $data = CreateFirewallRuleData::from([
            'name' => 'Custom Range',
            'port' => '8000-9000',
        ]);

        expect($data)
            ->name->toBe('Custom Range')
            ->port->toBe('8000-9000')
            ->ipAddress->toBeNull();
    });

    it('creates FirewallRuleCollectionData with empty array', function (): void {
        $data = FirewallRuleCollectionData::from([
            'rules' => [],
        ]);

        expect($data->rules)->toBeEmpty();
    });
});

describe('Jobs', function (): void {
    it('creates JobData from snake_case input', function (): void {
        $data = JobData::from([
            'id' => 1,
            'server_id' => 2,
            'command' => 'php artisan schedule:run',
            'user' => 'forge',
            'frequency' => 'minutely',
            'cron' => '* * * * *',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->command->toBe('php artisan schedule:run')
            ->user->toBe('forge')
            ->frequency->toBe('minutely')
            ->cron->toBe('* * * * *');
    });

    it('creates CreateJobData from snake_case input with defaults', function (): void {
        $data = CreateJobData::from([
            'command' => 'php artisan backup:run',
            'frequency' => 'daily',
        ]);

        expect($data)
            ->command->toBe('php artisan backup:run')
            ->frequency->toBe('daily')
            ->user->toBe('forge')
            ->minute->toBeNull()
            ->hour->toBeNull()
            ->day->toBeNull()
            ->month->toBeNull()
            ->weekday->toBeNull();
    });

    it('creates CreateJobData with custom cron fields', function (): void {
        $data = CreateJobData::from([
            'command' => 'php artisan report:generate',
            'frequency' => 'custom',
            'user' => 'root',
            'minute' => '0',
            'hour' => '3',
            'day' => '*',
            'month' => '*',
            'weekday' => '1',
        ]);

        expect($data)
            ->user->toBe('root')
            ->minute->toBe('0')
            ->hour->toBe('3')
            ->weekday->toBe('1');
    });

    it('creates JobCollectionData with empty array', function (): void {
        $data = JobCollectionData::from([
            'jobs' => [],
        ]);

        expect($data->jobs)->toBeEmpty();
    });
});

describe('Monitors', function (): void {
    it('creates MonitorData from snake_case input', function (): void {
        $data = MonitorData::from([
            'id' => 1,
            'server_id' => 2,
            'status' => 'installed',
            'type' => 'disk',
            'operator' => '>=',
            'threshold' => 90,
            'minutes' => 5,
            'state' => 'ok',
            'state_changed_at' => '2024-06-01T00:00:00Z',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->type->toBe('disk')
            ->operator->toBe('>=')
            ->threshold->toBe(90)
            ->minutes->toBe(5)
            ->state->toBe('ok')
            ->stateChangedAt->toBe('2024-06-01T00:00:00Z');
    });

    it('creates CreateMonitorData with optional fields', function (): void {
        $data = CreateMonitorData::from([
            'type' => 'cpu',
            'operator' => '>=',
            'threshold' => 80,
            'minutes' => 10,
        ]);

        expect($data)
            ->type->toBe('cpu')
            ->operator->toBe('>=')
            ->threshold->toBe(80)
            ->minutes->toBe(10);
    });

    it('creates MonitorCollectionData with empty array', function (): void {
        $data = MonitorCollectionData::from([
            'monitors' => [],
        ]);

        expect($data->monitors)->toBeEmpty();
    });
});

describe('NginxTemplates', function (): void {
    it('creates NginxTemplateData from snake_case input', function (): void {
        $data = NginxTemplateData::from([
            'id' => 1,
            'server_id' => 2,
            'name' => 'Default Laravel',
            'content' => 'server { listen 80; }',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->name->toBe('Default Laravel')
            ->content->toBe('server { listen 80; }');
    });

    it('creates CreateNginxTemplateData from snake_case input', function (): void {
        $data = CreateNginxTemplateData::from([
            'name' => 'Custom Template',
            'content' => 'server { listen 443 ssl; }',
        ]);

        expect($data)
            ->name->toBe('Custom Template')
            ->content->toBe('server { listen 443 ssl; }');
    });

    it('creates UpdateNginxTemplateData with partial fields', function (): void {
        $data = UpdateNginxTemplateData::from([
            'name' => 'Updated Template',
        ]);

        expect($data)
            ->name->toBe('Updated Template')
            ->content->toBeNull();
    });

    it('creates NginxTemplateCollectionData with empty array', function (): void {
        $data = NginxTemplateCollectionData::from([
            'templates' => [],
        ]);

        expect($data->templates)->toBeEmpty();
    });
});

describe('Php', function (): void {
    it('creates PhpVersionData from snake_case input', function (): void {
        $data = PhpVersionData::from([
            'version' => 'php82',
            'displayable_version' => 'PHP 8.2',
            'status' => 'installed',
            'used_as_default' => true,
            'used_on_cli' => true,
        ]);

        expect($data)
            ->version->toBe('php82')
            ->displayableVersion->toBe('PHP 8.2')
            ->status->toBe('installed')
            ->usedAsDefault->toBeTrue()
            ->usedOnCli->toBeTrue();
    });
});

describe('RedirectRules', function (): void {
    it('creates RedirectRuleData from snake_case input', function (): void {
        $data = RedirectRuleData::from([
            'id' => 1,
            'server_id' => 2,
            'site_id' => 3,
            'from' => '/old-page',
            'to' => '/new-page',
            'type' => 'redirect',
            'status' => 'installed',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->siteId->toBe(3)
            ->from->toBe('/old-page')
            ->to->toBe('/new-page')
            ->type->toBe('redirect');
    });

    it('creates CreateRedirectRuleData from snake_case input', function (): void {
        $data = CreateRedirectRuleData::from([
            'from' => '/old',
            'to' => '/new',
            'type' => 'permanent',
        ]);

        expect($data)
            ->from->toBe('/old')
            ->to->toBe('/new')
            ->type->toBe('permanent');
    });

    it('creates CreateRedirectRuleData with optional type', function (): void {
        $data = CreateRedirectRuleData::from([
            'from' => '/source',
            'to' => '/destination',
        ]);

        expect($data->type)->toBeNull();
    });

    it('creates RedirectRuleCollectionData with empty array', function (): void {
        $data = RedirectRuleCollectionData::from([
            'rules' => [],
        ]);

        expect($data->rules)->toBeEmpty();
    });
});

describe('SSHKeys', function (): void {
    it('creates SSHKeyData from snake_case input', function (): void {
        $data = SSHKeyData::from([
            'id' => 1,
            'server_id' => 2,
            'name' => 'deploy-key',
            'status' => 'installed',
            'username' => 'forge',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->name->toBe('deploy-key')
            ->status->toBe('installed')
            ->username->toBe('forge');
    });

    it('creates CreateSSHKeyData from snake_case input', function (): void {
        $data = CreateSSHKeyData::from([
            'name' => 'my-key',
            'key' => 'ssh-rsa AAAA...',
        ]);

        expect($data)
            ->name->toBe('my-key')
            ->key->toBe('ssh-rsa AAAA...');
    });

    it('creates SSHKeyCollectionData with empty array', function (): void {
        $data = SSHKeyCollectionData::from([
            'keys' => [],
        ]);

        expect($data->keys)->toBeEmpty();
    });
});

describe('SecurityRules', function (): void {
    it('creates SecurityRuleData from snake_case input', function (): void {
        $data = SecurityRuleData::from([
            'id' => 1,
            'server_id' => 2,
            'site_id' => 3,
            'name' => 'Admin Area',
            'path' => '/admin',
            'credentials' => 'admin:hashed_password',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->siteId->toBe(3)
            ->name->toBe('Admin Area')
            ->path->toBe('/admin')
            ->credentials->toBe('admin:hashed_password');
    });

    it('creates CreateSecurityRuleData from snake_case input', function (): void {
        $data = CreateSecurityRuleData::from([
            'name' => 'Staging Auth',
            'path' => '/staging',
            'credentials' => ['user1:pass1', 'user2:pass2'],
        ]);

        expect($data)
            ->name->toBe('Staging Auth')
            ->path->toBe('/staging')
            ->credentials->toBe(['user1:pass1', 'user2:pass2']);
    });

    it('creates SecurityRuleCollectionData with empty array', function (): void {
        $data = SecurityRuleCollectionData::from([
            'rules' => [],
        ]);

        expect($data->rules)->toBeEmpty();
    });
});

describe('Servers', function (): void {
    it('creates ServerData from snake_case input', function (): void {
        $data = ServerData::from([
            'id' => 1,
            'credential_id' => 5,
            'name' => 'production-web',
            'type' => 'app',
            'provider' => 'ocean2',
            'identifier' => 'do-123',
            'size' => '2gb',
            'region' => 'nyc1',
            'ubuntu_version' => '22.04',
            'db_status' => 'installed',
            'redis_status' => 'installed',
            'php_version' => 'php82',
            'php_cli_version' => 'php82',
            'opcache_status' => 'enabled',
            'database_type' => 'mysql8',
            'ip_address' => '192.168.1.100',
            'ssh_port' => 22,
            'private_ip_address' => '10.0.0.5',
            'local_public_key' => 'ssh-rsa AAAA...',
            'blackfire_status' => null,
            'papertrail_status' => null,
            'revoked' => false,
            'created_at' => '2024-01-01T00:00:00Z',
            'is_ready' => true,
            'tags' => ['production', 'web'],
            'network' => [],
        ]);

        expect($data)
            ->id->toBe(1)
            ->credentialId->toBe(5)
            ->name->toBe('production-web')
            ->type->toBe('app')
            ->provider->toBe('ocean2')
            ->ipAddress->toBe('192.168.1.100')
            ->sshPort->toBe(22)
            ->privateIpAddress->toBe('10.0.0.5')
            ->isReady->toBeTrue()
            ->revoked->toBeFalse()
            ->tags->toBe(['production', 'web']);
    });

    it('creates ServerData with nullable fields', function (): void {
        $data = ServerData::from([
            'id' => 2,
            'credential_id' => null,
            'name' => 'minimal',
            'type' => 'app',
            'provider' => 'custom',
            'identifier' => null,
            'size' => '1gb',
            'region' => 'us-east',
            'ubuntu_version' => '22.04',
            'db_status' => null,
            'redis_status' => null,
            'php_version' => null,
            'php_cli_version' => null,
            'opcache_status' => null,
            'database_type' => null,
            'ip_address' => null,
            'ssh_port' => 22,
            'private_ip_address' => null,
            'local_public_key' => null,
            'blackfire_status' => null,
            'papertrail_status' => null,
            'revoked' => false,
            'created_at' => '2024-01-01T00:00:00Z',
            'is_ready' => false,
            'tags' => [],
            'network' => [],
        ]);

        expect($data)
            ->credentialId->toBeNull()
            ->identifier->toBeNull()
            ->dbStatus->toBeNull()
            ->phpVersion->toBeNull()
            ->ipAddress->toBeNull()
            ->isReady->toBeFalse();
    });

    it('creates CreateServerData from snake_case input', function (): void {
        $data = CreateServerData::from([
            'name' => 'new-server',
            'provider' => 'ocean2',
            'type' => 'app',
            'ubuntu_version' => '24.04',
            'credential_id' => 1,
            'php_version' => 'php82',
            'database_type' => 'mysql8',
            'database' => 'forge',
            'tags' => ['production', 'api'],
            'ocean2' => ['region_id' => 'nyc1', 'size_id' => 's-1vcpu-1gb'],
        ]);

        expect($data)
            ->name->toBe('new-server')
            ->provider->toBe('ocean2')
            ->type->toBe('app')
            ->ubuntuVersion->toBe('24.04')
            ->credentialId->toBe(1)
            ->phpVersion->toBe('php82')
            ->databaseType->toBe('mysql8')
            ->database->toBe('forge')
            ->tags->toBe(['production', 'api'])
            ->ocean2->toBe(['region_id' => 'nyc1', 'size_id' => 's-1vcpu-1gb']);
    });

    it('creates EventData from snake_case input', function (): void {
        $data = EventData::from([
            'id' => 1,
            'server_id' => 2,
            'run_as' => 'root',
            'description' => 'Server rebooted',
            'status' => 'success',
            'created_at' => '2024-01-01T00:00:00Z',
            'output' => 'Reboot completed successfully',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->runAs->toBe('root')
            ->description->toBe('Server rebooted')
            ->output->toBe('Reboot completed successfully');
    });

    it('creates ServerCollectionData with empty array', function (): void {
        $data = ServerCollectionData::from([
            'servers' => [],
        ]);

        expect($data->servers)->toBeEmpty();
    });
});

describe('Sites', function (): void {
    it('creates SiteData from snake_case input', function (): void {
        $data = SiteData::from([
            'id' => 1,
            'server_id' => 2,
            'name' => 'example.com',
            'aliases' => ['www.example.com'],
            'directory' => '/home/forge/example.com',
            'wildcards' => false,
            'status' => 'installed',
            'repository' => 'user/repo',
            'repository_provider' => 'github',
            'repository_branch' => 'main',
            'repository_status' => 'installed',
            'quick_deploy' => true,
            'deployment_status' => null,
            'project_type' => 'php',
            'app' => null,
            'app_status' => null,
            'hipchat_room' => null,
            'slack_channel' => '#deployments',
            'telegram_chat_id' => null,
            'telegram_chat_title' => null,
            'teams_webhook_url' => null,
            'discord_webhook_url' => null,
            'username' => 'forge',
            'balancing_status' => null,
            'created_at' => '2024-01-01T00:00:00Z',
            'deployment_url' => 'https://deploy.example.com',
            'is_secured' => true,
            'php_version' => 'php82',
            'tags' => ['production'],
            'failure_deployment_emails' => ['admin@example.com'],
            'telegram_secret' => null,
            'web_directory' => '/public',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->name->toBe('example.com')
            ->aliases->toBe(['www.example.com'])
            ->wildcards->toBeFalse()
            ->repository->toBe('user/repo')
            ->repositoryProvider->toBe('github')
            ->repositoryBranch->toBe('main')
            ->quickDeploy->toBeTrue()
            ->projectType->toBe('php')
            ->username->toBe('forge')
            ->isSecured->toBeTrue()
            ->webDirectory->toBe('/public');
    });

    it('creates CreateSiteData from snake_case input', function (): void {
        $data = CreateSiteData::from([
            'domain' => 'newsite.com',
            'project_type' => 'php',
            'aliases' => ['www.newsite.com'],
            'directory' => '/public',
            'isolated' => true,
            'username' => 'newsite',
            'database' => 'newsite_db',
            'php_version' => 'php82',
            'nginx_template' => 5,
        ]);

        expect($data)
            ->domain->toBe('newsite.com')
            ->projectType->toBe('php')
            ->aliases->toBe(['www.newsite.com'])
            ->directory->toBe('/public')
            ->isolated->toBeTrue()
            ->username->toBe('newsite')
            ->database->toBe('newsite_db')
            ->phpVersion->toBe('php82')
            ->nginxTemplate->toBe(5);
    });

    it('creates ExecuteSiteCommandData from snake_case input', function (): void {
        $data = ExecuteSiteCommandData::from([
            'command' => 'php artisan migrate --force',
        ]);

        expect($data->command)->toBe('php artisan migrate --force');
    });

    it('creates InstallGitRepositoryData from snake_case input', function (): void {
        $data = InstallGitRepositoryData::from([
            'provider' => 'github',
            'repository' => 'user/repo',
            'branch' => 'main',
            'composer' => true,
        ]);

        expect($data)
            ->provider->toBe('github')
            ->repository->toBe('user/repo')
            ->branch->toBe('main')
            ->composer->toBeTrue();
    });

    it('creates SiteCommandData from snake_case input', function (): void {
        $data = SiteCommandData::from([
            'id' => 1,
            'server_id' => 2,
            'site_id' => 3,
            'command' => 'php artisan cache:clear',
            'status' => 'finished',
            'output' => 'Cache cleared successfully.',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->siteId->toBe(3)
            ->command->toBe('php artisan cache:clear')
            ->status->toBe('finished')
            ->output->toBe('Cache cleared successfully.');
    });

    it('creates UpdateSiteData with partial fields', function (): void {
        $data = UpdateSiteData::from([
            'directory' => '/public_html',
        ]);

        expect($data)
            ->directory->toBe('/public_html')
            ->aliases->toBeNull()
            ->isolated->toBeNull();
    });

    it('creates UpdateGitRepositoryData from snake_case input', function (): void {
        $data = UpdateGitRepositoryData::from([
            'branch' => 'develop',
        ]);

        expect($data->branch)->toBe('develop');
    });

    it('creates SiteCollectionData with empty array', function (): void {
        $data = SiteCollectionData::from([
            'sites' => [],
        ]);

        expect($data->sites)->toBeEmpty();
    });
});

describe('User', function (): void {
    it('creates UserData from snake_case input', function (): void {
        $data = UserData::from([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'card_last_four' => '4242',
            'connected_to_github' => '1',
            'connected_to_gitlab' => null,
            'connected_to_bitbucket' => null,
            'connected_to_bitbucket_two' => null,
            'connected_to_digitalocean' => '1',
            'connected_to_linode' => null,
            'connected_to_vultr' => null,
            'connected_to_aws' => null,
            'connected_to_hetzner' => null,
            'ready_for_billing' => '1',
            'stripe_is_active' => '1',
            'can_create_servers' => true,
        ]);

        expect($data)
            ->id->toBe(1)
            ->name->toBe('John Doe')
            ->email->toBe('john@example.com')
            ->cardLastFour->toBe('4242')
            ->connectedToGithub->toBe('1')
            ->connectedToGitlab->toBeNull()
            ->connectedToDigitalocean->toBe('1')
            ->readyForBilling->toBe('1')
            ->stripeIsActive->toBe('1')
            ->canCreateServers->toBeTrue();
    });

    it('creates UserData with minimal fields', function (): void {
        $data = UserData::from([
            'id' => 2,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        expect($data)
            ->id->toBe(2)
            ->name->toBe('Jane Doe')
            ->email->toBe('jane@example.com')
            ->cardLastFour->toBeNull()
            ->connectedToGithub->toBeNull()
            ->canCreateServers->toBeNull();
    });
});

describe('Webhooks', function (): void {
    it('creates WebhookData from snake_case input', function (): void {
        $data = WebhookData::from([
            'id' => 1,
            'server_id' => 2,
            'site_id' => 3,
            'url' => 'https://hooks.example.com/deploy',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->siteId->toBe(3)
            ->url->toBe('https://hooks.example.com/deploy');
    });

    it('creates CreateWebhookData from snake_case input', function (): void {
        $data = CreateWebhookData::from([
            'url' => 'https://hooks.example.com/notify',
        ]);

        expect($data->url)->toBe('https://hooks.example.com/notify');
    });

    it('creates WebhookCollectionData with empty array', function (): void {
        $data = WebhookCollectionData::from([
            'webhooks' => [],
        ]);

        expect($data->webhooks)->toBeEmpty();
    });
});

describe('Workers', function (): void {
    it('creates WorkerData from snake_case input', function (): void {
        $data = WorkerData::from([
            'id' => 1,
            'server_id' => 2,
            'site_id' => 3,
            'connection' => 'redis',
            'command' => 'php8.2 artisan queue:work',
            'queue' => 'default',
            'timeout' => 60,
            'sleep' => 3,
            'tries' => 3,
            'environment' => 'production',
            'daemon' => 1,
            'status' => 'running',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        expect($data)
            ->id->toBe(1)
            ->serverId->toBe(2)
            ->siteId->toBe(3)
            ->connection->toBe('redis')
            ->command->toBe('php8.2 artisan queue:work')
            ->queue->toBe('default')
            ->timeout->toBe(60)
            ->sleep->toBe(3)
            ->tries->toBe(3)
            ->environment->toBe('production')
            ->daemon->toBe(1);
    });

    it('creates CreateWorkerData from snake_case input with defaults', function (): void {
        $data = CreateWorkerData::from([
            'connection' => 'sqs',
            'queue' => 'high,default',
        ]);

        expect($data)
            ->connection->toBe('sqs')
            ->queue->toBe('high,default')
            ->timeout->toBeNull()
            ->sleep->toBeNull()
            ->tries->toBeNull()
            ->daemon->toBeNull()
            ->force->toBeNull();
    });

    it('creates CreateWorkerData with all optional fields', function (): void {
        $data = CreateWorkerData::from([
            'connection' => 'redis',
            'queue' => 'default',
            'timeout' => 300,
            'sleep' => 5,
            'tries' => 5,
            'daemon' => true,
            'force' => true,
        ]);

        expect($data)
            ->timeout->toBe(300)
            ->sleep->toBe(5)
            ->tries->toBe(5)
            ->daemon->toBeTrue()
            ->force->toBeTrue();
    });

    it('creates WorkerCollectionData with empty array', function (): void {
        $data = WorkerCollectionData::from([
            'workers' => [],
        ]);

        expect($data->workers)->toBeEmpty();
    });

    it('creates WorkerCollectionData via fromResponse', function (): void {
        $data = WorkerCollectionData::fromResponse([
            'workers' => [
                [
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
                ],
            ],
        ]);

        expect($data->workers)
            ->toHaveCount(1)
            ->and($data->workers[0])
            ->toBeInstanceOf(WorkerData::class)
            ->connection->toBe('redis');
    });
});
