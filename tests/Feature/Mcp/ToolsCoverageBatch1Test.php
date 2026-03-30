<?php

declare(strict_types=1);

use App\Integrations\Forge\Data\Sites\ExecuteSiteCommandData;
use App\Integrations\Forge\ForgeClient;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Backups\{CreateBackupConfigurationTool, DeleteBackupConfigurationTool, DeleteBackupTool, GetBackupConfigurationTool, ListBackupConfigurationsTool, RestoreBackupTool, UpdateBackupConfigurationTool};
use App\Mcp\Tools\Daemons\{CreateDaemonTool, DeleteDaemonTool, GetDaemonTool, ListDaemonsTool, RestartDaemonTool};
use App\Mcp\Tools\Commands\{ExecuteSiteCommandTool, GetSiteCommandTool, ListCommandHistoryTool};
use App\Mcp\Tools\Certificates\{ActivateCertificateTool, DeleteCertificateTool, GetCertificateSigningRequestTool, GetCertificateTool, InstallCertificateTool, ListCertificatesTool, ObtainLetsEncryptCertificateTool};
use App\Mcp\Tools\Configuration\{GetEnvFileTool, GetNginxConfigTool, UpdateEnvFileTool, UpdateNginxConfigTool};
use App\Integrations\Forge\Resources\{BackupResource, CertificateResource, DaemonResource, SiteResource};
use App\Integrations\Forge\Data\Backups\{BackupConfigurationCollectionData, BackupConfigurationData, CreateBackupConfigurationData, UpdateBackupConfigurationData};
use App\Integrations\Forge\Data\Daemons\{CreateDaemonData, DaemonCollectionData, DaemonData};
use App\Integrations\Forge\Data\Certificates\{CertificateCollectionData, CertificateData};

beforeEach(function (): void {
    config([
        'services.forge.api_token' => 'test-token',
        'services.forge.organization' => 'test-org',
    ]);
});

function createMockBackupConfiguration(int $id = 1, string $provider = 's3'): BackupConfigurationData
{
    return BackupConfigurationData::from([
        'id' => $id,
        'server_id' => 1,
        'day_of_week' => null,
        'time' => '00:00',
        'provider' => $provider,
        'provider_name' => 'Amazon S3',
        'last_backup_time' => null,
        'created_at' => '2024-01-01T00:00:00Z',
    ]);
}

function createMockCertificateForBatch(int $id = 1, string $domain = 'example.com'): CertificateData
{
    return CertificateData::from([
        'id' => $id,
        'server_id' => 1,
        'site_id' => 1,
        'domain' => $domain,
        'request_status' => 'created',
        'status' => 'installed',
        'type' => 'letsencrypt',
        'active' => true,
        'existing' => false,
        'expires_at' => '2025-01-01T00:00:00Z',
        'created_at' => '2024-01-01T00:00:00Z',
        'activation_error' => null,
    ]);
}

function createMockDaemonForBatch(int $id = 1): DaemonData
{
    return DaemonData::from([
        'id' => $id,
        'server_id' => 1,
        'command' => 'php artisan horizon',
        'user' => 'forge',
        'status' => 'installed',
        'directory' => '/home/forge/app',
        'created_at' => '2024-01-01T00:00:00Z',
    ]);
}

// ============================================================
// BACKUPS
// ============================================================

describe('ListBackupConfigurationsTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListBackupConfigurationsTool::class, []);

        $response->assertHasErrors();
    });

    it('lists backup configurations successfully', function (): void {
        $mockBackup = createMockBackupConfiguration();
        $collection = new BackupConfigurationCollectionData(backups: [$mockBackup]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($collection): void {
            $backupResource = Mockery::mock(BackupResource::class);
            $backupResource->shouldReceive('listConfigurations')->with(1, null, 30)->once()->andReturn($collection);
            $mock->shouldReceive('backups')->once()->andReturn($backupResource);
        });

        $response = ForgeServer::tool(ListBackupConfigurationsTool::class, ['server_id' => 1]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('"count": 1')
            ->assertSee('s3');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $backupResource = Mockery::mock(BackupResource::class);
            $backupResource->shouldReceive('listConfigurations')->once()->andThrow(new Exception('API Error'));
            $mock->shouldReceive('backups')->once()->andReturn($backupResource);
        });

        $response = ForgeServer::tool(ListBackupConfigurationsTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('API Error');
    });
});

describe('GetBackupConfigurationTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetBackupConfigurationTool::class, []);

        $response->assertHasErrors();
    });

    it('gets backup configuration successfully', function (): void {
        $mockBackup = createMockBackupConfiguration();

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockBackup): void {
            $backupResource = Mockery::mock(BackupResource::class);
            $backupResource->shouldReceive('getConfiguration')->with(1, 10)->once()->andReturn($mockBackup);
            $mock->shouldReceive('backups')->once()->andReturn($backupResource);
        });

        $response = ForgeServer::tool(GetBackupConfigurationTool::class, [
            'server_id' => 1,
            'backup_id' => 10,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('s3');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $backupResource = Mockery::mock(BackupResource::class);
            $backupResource->shouldReceive('getConfiguration')->once()->andThrow(new Exception('Backup not found'));
            $mock->shouldReceive('backups')->once()->andReturn($backupResource);
        });

        $response = ForgeServer::tool(GetBackupConfigurationTool::class, [
            'server_id' => 1,
            'backup_id' => 999,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Backup not found');
    });
});

describe('CreateBackupConfigurationTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(CreateBackupConfigurationTool::class, []);

        $response->assertHasErrors();
    });

    it('validates provider is required', function (): void {
        $response = ForgeServer::tool(CreateBackupConfigurationTool::class, [
            'server_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('creates backup configuration successfully', function (): void {
        $mockBackup = createMockBackupConfiguration();

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockBackup): void {
            $backupResource = Mockery::mock(BackupResource::class);
            $backupResource->shouldReceive('createConfiguration')
                ->with(1, Mockery::type(CreateBackupConfigurationData::class))
                ->once()
                ->andReturn($mockBackup);
            $mock->shouldReceive('backups')->once()->andReturn($backupResource);
        });

        $response = ForgeServer::tool(CreateBackupConfigurationTool::class, [
            'server_id' => 1,
            'provider' => 's3',
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Backup configuration created successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $backupResource = Mockery::mock(BackupResource::class);
            $backupResource->shouldReceive('createConfiguration')->once()->andThrow(new Exception('Invalid provider'));
            $mock->shouldReceive('backups')->once()->andReturn($backupResource);
        });

        $response = ForgeServer::tool(CreateBackupConfigurationTool::class, [
            'server_id' => 1,
            'provider' => 'invalid',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Invalid provider');
    });
});

describe('UpdateBackupConfigurationTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(UpdateBackupConfigurationTool::class, []);

        $response->assertHasErrors();
    });

    it('validates backup_id is required', function (): void {
        $response = ForgeServer::tool(UpdateBackupConfigurationTool::class, [
            'server_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('updates backup configuration successfully', function (): void {
        $mockBackup = createMockBackupConfiguration();

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockBackup): void {
            $backupResource = Mockery::mock(BackupResource::class);
            $backupResource->shouldReceive('updateConfiguration')
                ->with(1, 10, Mockery::type(UpdateBackupConfigurationData::class))
                ->once()
                ->andReturn($mockBackup);
            $mock->shouldReceive('backups')->once()->andReturn($backupResource);
        });

        $response = ForgeServer::tool(UpdateBackupConfigurationTool::class, [
            'server_id' => 1,
            'backup_id' => 10,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Backup configuration updated successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $backupResource = Mockery::mock(BackupResource::class);
            $backupResource->shouldReceive('updateConfiguration')->once()->andThrow(new Exception('Update failed'));
            $mock->shouldReceive('backups')->once()->andReturn($backupResource);
        });

        $response = ForgeServer::tool(UpdateBackupConfigurationTool::class, [
            'server_id' => 1,
            'backup_id' => 10,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Update failed');
    });
});

describe('DeleteBackupConfigurationTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteBackupConfigurationTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes backup configuration successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $backupResource = Mockery::mock(BackupResource::class);
            $backupResource->shouldReceive('deleteConfiguration')->with(1, 10)->once();
            $mock->shouldReceive('backups')->once()->andReturn($backupResource);
        });

        $response = ForgeServer::tool(DeleteBackupConfigurationTool::class, [
            'server_id' => 1,
            'backup_id' => 10,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Backup configuration deleted successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $backupResource = Mockery::mock(BackupResource::class);
            $backupResource->shouldReceive('deleteConfiguration')->once()->andThrow(new Exception('Delete failed'));
            $mock->shouldReceive('backups')->once()->andReturn($backupResource);
        });

        $response = ForgeServer::tool(DeleteBackupConfigurationTool::class, [
            'server_id' => 1,
            'backup_id' => 10,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Delete failed');
    });
});

describe('RestoreBackupTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(RestoreBackupTool::class, []);

        $response->assertHasErrors();
    });

    it('validates all three IDs are required', function (): void {
        $response = ForgeServer::tool(RestoreBackupTool::class, [
            'server_id' => 1,
            'backup_config_id' => 10,
        ]);

        $response->assertHasErrors();
    });

    it('restores backup successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $backupResource = Mockery::mock(BackupResource::class);
            $backupResource->shouldReceive('restore')->with(1, 10, 20)->once();
            $mock->shouldReceive('backups')->once()->andReturn($backupResource);
        });

        $response = ForgeServer::tool(RestoreBackupTool::class, [
            'server_id' => 1,
            'backup_config_id' => 10,
            'backup_id' => 20,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Backup restore initiated successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $backupResource = Mockery::mock(BackupResource::class);
            $backupResource->shouldReceive('restore')->once()->andThrow(new Exception('Restore failed'));
            $mock->shouldReceive('backups')->once()->andReturn($backupResource);
        });

        $response = ForgeServer::tool(RestoreBackupTool::class, [
            'server_id' => 1,
            'backup_config_id' => 10,
            'backup_id' => 20,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Restore failed');
    });
});

describe('DeleteBackupTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteBackupTool::class, []);

        $response->assertHasErrors();
    });

    it('validates all three IDs are required', function (): void {
        $response = ForgeServer::tool(DeleteBackupTool::class, [
            'server_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('deletes backup successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $backupResource = Mockery::mock(BackupResource::class);
            $backupResource->shouldReceive('delete')->with(1, 10, 20)->once();
            $mock->shouldReceive('backups')->once()->andReturn($backupResource);
        });

        $response = ForgeServer::tool(DeleteBackupTool::class, [
            'server_id' => 1,
            'backup_config_id' => 10,
            'backup_id' => 20,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Backup deleted successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $backupResource = Mockery::mock(BackupResource::class);
            $backupResource->shouldReceive('delete')->once()->andThrow(new Exception('Backup not found'));
            $mock->shouldReceive('backups')->once()->andReturn($backupResource);
        });

        $response = ForgeServer::tool(DeleteBackupTool::class, [
            'server_id' => 1,
            'backup_config_id' => 10,
            'backup_id' => 20,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Backup not found');
    });
});

// ============================================================
// CERTIFICATES
// ============================================================

describe('ListCertificatesTool (coverage)', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListCertificatesTool::class, []);

        $response->assertHasErrors();
    });

    it('lists certificates successfully', function (): void {
        $mockCert = createMockCertificateForBatch();
        $collection = new CertificateCollectionData(certificates: [$mockCert]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($collection): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('list')->with(1, 2, null, 30)->once()->andReturn($collection);
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ListCertificatesTool::class, [
            'server_id' => 1,
            'site_id' => 2,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('example.com');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('list')->once()->andThrow(new Exception('Server not found'));
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ListCertificatesTool::class, [
            'server_id' => 999,
            'site_id' => 1,
        ]);

        $response->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Server not found')
            ->assertSee('Failed to retrieve certificates');
    });
});

describe('GetCertificateTool (coverage)', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetCertificateTool::class, []);

        $response->assertHasErrors();
    });

    it('gets certificate details successfully', function (): void {
        $mockCert = createMockCertificateForBatch();

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockCert): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('get')->with(1, 2, 3)->once()->andReturn($mockCert);
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(GetCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'domain_id' => 3,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('example.com')
            ->assertSee('letsencrypt');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('get')->once()->andThrow(new Exception('Certificate not found'));
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(GetCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'domain_id' => 999,
        ]);

        $response->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Certificate not found')
            ->assertSee('Failed to retrieve certificate');
    });
});

describe('InstallCertificateTool (coverage)', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(InstallCertificateTool::class, []);

        $response->assertHasErrors();
    });

    it('installs certificate successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('activate')->with(1, 2, 3)->once();
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(InstallCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'domain_id' => 3,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Certificate installed and activated successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('activate')->once()->andThrow(new Exception('Activation failed'));
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(InstallCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'domain_id' => 3,
        ]);

        $response->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Activation failed')
            ->assertSee('Failed to install certificate');
    });
});

describe('ObtainLetsEncryptCertificateTool (coverage)', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, []);

        $response->assertHasErrors();
    });

    it('obtains certificate successfully', function (): void {
        $mockCert = createMockCertificateForBatch();

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockCert): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('obtainLetsEncrypt')
                ->with(1, 2, 3)
                ->once()
                ->andReturn($mockCert);
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'domain_id' => 3,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee("Let's Encrypt certificate installation initiated");
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('obtainLetsEncrypt')->once()->andThrow(new Exception('DNS validation failed'));
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'domain_id' => 3,
        ]);

        $response->assertOk()
            ->assertSee('"success": false')
            ->assertSee('DNS validation failed')
            ->assertSee('Failed to obtain certificate');
    });
});

describe('ActivateCertificateTool (coverage)', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ActivateCertificateTool::class, []);

        $response->assertHasErrors();
    });

    it('activates certificate successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('activate')->with(1, 2, 3)->once();
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ActivateCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'domain_id' => 3,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Certificate activated successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('activate')->once()->andThrow(new Exception('Cannot activate'));
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ActivateCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'domain_id' => 3,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Cannot activate');
    });
});

describe('DeleteCertificateTool (coverage)', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteCertificateTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes certificate successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('delete')->with(1, 2, 3)->once();
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(DeleteCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'domain_id' => 3,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Certificate deleted successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('delete')->once()->andThrow(new Exception('Cannot delete active certificate'));
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(DeleteCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'domain_id' => 3,
        ]);

        $response->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Cannot delete active certificate')
            ->assertSee('Failed to delete certificate');
    });
});

describe('GetCertificateSigningRequestTool (coverage)', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetCertificateSigningRequestTool::class, []);

        $response->assertHasErrors();
    });

    it('gets CSR successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('signingRequest')
                ->with(1, 2, 3)
                ->once()
                ->andReturn('-----BEGIN CERTIFICATE REQUEST-----\nMIICYzCC...\n-----END CERTIFICATE REQUEST-----');
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(GetCertificateSigningRequestTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'domain_id' => 3,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('CERTIFICATE REQUEST');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('signingRequest')->once()->andThrow(new Exception('CSR not available'));
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(GetCertificateSigningRequestTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'domain_id' => 3,
        ]);

        $response->assertOk()
            ->assertSee('"success": false')
            ->assertSee('CSR not available')
            ->assertSee('Failed to retrieve certificate signing request');
    });
});

// ============================================================
// COMMANDS
// ============================================================

describe('ExecuteSiteCommandTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ExecuteSiteCommandTool::class, []);

        $response->assertHasErrors();
    });

    it('validates command is required', function (): void {
        $response = ForgeServer::tool(ExecuteSiteCommandTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('executes command successfully', function (): void {
        $commandArray = [
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'command' => 'php artisan cache:clear',
            'status' => 'executing',
            'output' => null,
            'created_at' => '2024-01-01T00:00:00Z',
        ];

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($commandArray): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('executeCommand')
                ->with(1, 2, Mockery::type(ExecuteSiteCommandData::class))
                ->once()
                ->andReturn($commandArray);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ExecuteSiteCommandTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'command' => 'php artisan cache:clear',
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Command queued for execution')
            ->assertSee('php artisan cache:clear');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('executeCommand')->once()->andThrow(new Exception('Command execution failed'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ExecuteSiteCommandTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'command' => 'invalid-command',
        ]);

        $response->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Command execution failed')
            ->assertSee('Failed to execute command');
    });
});

describe('GetSiteCommandTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetSiteCommandTool::class, []);

        $response->assertHasErrors();
    });

    it('validates command_id is required', function (): void {
        $response = ForgeServer::tool(GetSiteCommandTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('gets command details successfully', function (): void {
        $commandArray = [
            'id' => 5,
            'server_id' => 1,
            'site_id' => 2,
            'command' => 'php artisan migrate',
            'status' => 'finished',
            'output' => 'Migration successful',
            'created_at' => '2024-01-01T00:00:00Z',
        ];

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($commandArray): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('getCommand')->with(1, 2, 5)->once()->andReturn($commandArray);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetSiteCommandTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'command_id' => 5,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('php artisan migrate');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('getCommand')->once()->andThrow(new Exception('Command not found'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetSiteCommandTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'command_id' => 999,
        ]);

        $response->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Command not found')
            ->assertSee('Failed to retrieve command details');
    });
});

describe('ListCommandHistoryTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListCommandHistoryTool::class, []);

        $response->assertHasErrors();
    });

    it('lists command history successfully', function (): void {
        $commandsArray = [
            [
                'id' => 1,
                'server_id' => 1,
                'site_id' => 2,
                'command' => 'php artisan cache:clear',
                'status' => 'finished',
                'output' => null,
                'created_at' => '2024-01-01T00:00:00Z',
            ],
            [
                'id' => 2,
                'server_id' => 1,
                'site_id' => 2,
                'command' => 'php artisan migrate',
                'status' => 'finished',
                'output' => null,
                'created_at' => '2024-01-02T00:00:00Z',
            ],
        ];

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($commandsArray): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('commandHistory')->with(1, 2, null, 30)->once()->andReturn($commandsArray);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ListCommandHistoryTool::class, [
            'server_id' => 1,
            'site_id' => 2,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('"count": 2')
            ->assertSee('php artisan cache:clear')
            ->assertSee('php artisan migrate');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('commandHistory')->once()->andThrow(new Exception('Site not found'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(ListCommandHistoryTool::class, [
            'server_id' => 1,
            'site_id' => 999,
        ]);

        $response->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Site not found')
            ->assertSee('Failed to retrieve command history');
    });
});

// ============================================================
// CONFIGURATION
// ============================================================

describe('GetEnvFileTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetEnvFileTool::class, []);

        $response->assertHasErrors();
    });

    it('gets env file successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('getEnvFile')
                ->with(1, 2)
                ->once()
                ->andReturn("APP_NAME=Laravel\nAPP_ENV=production");
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetEnvFileTool::class, [
            'server_id' => 1,
            'site_id' => 2,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('APP_NAME=Laravel');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('getEnvFile')->once()->andThrow(new Exception('Access denied'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetEnvFileTool::class, [
            'server_id' => 1,
            'site_id' => 2,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Access denied');
    });
});

describe('UpdateEnvFileTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(UpdateEnvFileTool::class, []);

        $response->assertHasErrors();
    });

    it('validates content is required', function (): void {
        $response = ForgeServer::tool(UpdateEnvFileTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('updates env file successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('updateEnvFile')
                ->with(1, 2, "APP_NAME=MyApp\nAPP_ENV=production")
                ->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UpdateEnvFileTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'content' => "APP_NAME=MyApp\nAPP_ENV=production",
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Environment file updated successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('updateEnvFile')->once()->andThrow(new Exception('Update failed'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UpdateEnvFileTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'content' => 'APP_NAME=MyApp',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Update failed');
    });
});

describe('GetNginxConfigTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetNginxConfigTool::class, []);

        $response->assertHasErrors();
    });

    it('gets nginx config successfully', function (): void {
        $nginxConfig = 'server { listen 80; server_name example.com; }';

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($nginxConfig): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('getNginxConfig')->with(1, 2)->once()->andReturn($nginxConfig);
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetNginxConfigTool::class, [
            'server_id' => 1,
            'site_id' => 2,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('server_name example.com');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('getNginxConfig')->once()->andThrow(new Exception('Config not found'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(GetNginxConfigTool::class, [
            'server_id' => 1,
            'site_id' => 2,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Config not found');
    });
});

describe('UpdateNginxConfigTool', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(UpdateNginxConfigTool::class, []);

        $response->assertHasErrors();
    });

    it('validates content is required', function (): void {
        $response = ForgeServer::tool(UpdateNginxConfigTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('updates nginx config successfully', function (): void {
        $newConfig = 'server { listen 443 ssl; server_name example.com; }';

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($newConfig): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('updateNginxConfig')->with(1, 2, $newConfig)->once();
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UpdateNginxConfigTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'content' => $newConfig,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Nginx configuration updated successfully');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $siteResource = Mockery::mock(SiteResource::class);
            $siteResource->shouldReceive('updateNginxConfig')->once()->andThrow(new Exception('Invalid nginx config'));
            $mock->shouldReceive('sites')->once()->andReturn($siteResource);
        });

        $response = ForgeServer::tool(UpdateNginxConfigTool::class, [
            'server_id' => 1,
            'site_id' => 2,
            'content' => 'invalid config',
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Invalid nginx config');
    });
});

// ============================================================
// DAEMONS
// ============================================================

describe('ListDaemonsTool (coverage)', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(ListDaemonsTool::class, []);

        $response->assertHasErrors();
    });

    it('lists daemons successfully', function (): void {
        $mockDaemon = createMockDaemonForBatch();
        $collection = new DaemonCollectionData(daemons: [$mockDaemon]);

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($collection): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('list')->with(1, null, 30)->once()->andReturn($collection);
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(ListDaemonsTool::class, ['server_id' => 1]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('php artisan horizon')
            ->assertSee('"count": 1');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('list')->once()->andThrow(new Exception('Server unavailable'));
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(ListDaemonsTool::class, ['server_id' => 1]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Server unavailable');
    });
});

describe('GetDaemonTool (coverage)', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(GetDaemonTool::class, []);

        $response->assertHasErrors();
    });

    it('gets daemon details successfully', function (): void {
        $mockDaemon = createMockDaemonForBatch();

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockDaemon): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('get')->with(1, 5)->once()->andReturn($mockDaemon);
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(GetDaemonTool::class, [
            'server_id' => 1,
            'daemon_id' => 5,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('php artisan horizon')
            ->assertSee('forge');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('get')->once()->andThrow(new Exception('Daemon not found'));
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(GetDaemonTool::class, [
            'server_id' => 1,
            'daemon_id' => 999,
        ]);

        $response->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Daemon not found')
            ->assertSee('Failed to retrieve daemon');
    });
});

describe('CreateDaemonTool (coverage)', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(CreateDaemonTool::class, []);

        $response->assertHasErrors();
    });

    it('validates command and directory are required', function (): void {
        $response = ForgeServer::tool(CreateDaemonTool::class, [
            'server_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('creates daemon successfully', function (): void {
        $mockDaemon = createMockDaemonForBatch();

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockDaemon): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('create')
                ->with(1, Mockery::type(CreateDaemonData::class))
                ->once()
                ->andReturn($mockDaemon);
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(CreateDaemonTool::class, [
            'server_id' => 1,
            'command' => 'php artisan horizon',
            'directory' => '/home/forge/app',
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Daemon created successfully')
            ->assertSee('php artisan horizon');
    });

    it('creates daemon with optional parameters', function (): void {
        $mockDaemon = createMockDaemonForBatch();

        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock) use ($mockDaemon): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('create')
                ->with(1, Mockery::type(CreateDaemonData::class))
                ->once()
                ->andReturn($mockDaemon);
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(CreateDaemonTool::class, [
            'server_id' => 1,
            'command' => 'php artisan horizon',
            'directory' => '/home/forge/app',
            'user' => 'forge',
            'processes' => 3,
            'startsecs' => 5,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('create')->once()->andThrow(new Exception('Failed to create daemon'));
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(CreateDaemonTool::class, [
            'server_id' => 1,
            'command' => 'php artisan horizon',
            'directory' => '/home/forge/app',
        ]);

        $response->assertOk()
            ->assertSee('"success": false')
            ->assertSee('Failed to create daemon');
    });
});

describe('RestartDaemonTool (coverage)', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(RestartDaemonTool::class, []);

        $response->assertHasErrors();
    });

    it('restarts daemon successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('restart')->with(1, 5)->once();
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(RestartDaemonTool::class, [
            'server_id' => 1,
            'daemon_id' => 5,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Daemon restarted');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('restart')->once()->andThrow(new Exception('Restart failed'));
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(RestartDaemonTool::class, [
            'server_id' => 1,
            'daemon_id' => 5,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Restart failed');
    });
});

describe('DeleteDaemonTool (coverage)', function (): void {
    it('validates required parameters', function (): void {
        $response = ForgeServer::tool(DeleteDaemonTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes daemon successfully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('delete')->with(1, 5)->once();
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(DeleteDaemonTool::class, [
            'server_id' => 1,
            'daemon_id' => 5,
        ]);

        $response->assertOk()
            ->assertSee('"success": true')
            ->assertSee('Daemon PERMANENTLY DELETED');
    });

    it('handles API errors gracefully', function (): void {
        $this->mock(ForgeClient::class, function (Mockery\MockInterface $mock): void {
            $daemonResource = Mockery::mock(DaemonResource::class);
            $daemonResource->shouldReceive('delete')->once()->andThrow(new Exception('Delete failed'));
            $mock->shouldReceive('daemons')->once()->andReturn($daemonResource);
        });

        $response = ForgeServer::tool(DeleteDaemonTool::class, [
            'server_id' => 1,
            'daemon_id' => 5,
        ]);

        $response->assertOk()->assertSee('"success": false')->assertSee('Delete failed');
    });
});
