<?php

declare(strict_types=1);

use App\Integrations\Forge\ForgeClient;
use App\Integrations\Forge\Resources\CertificateResource;
use App\Mcp\Servers\ForgeServer;
use App\Mcp\Tools\Certificates\{ActivateCertificateTool, DeleteCertificateTool, GetCertificateSigningRequestTool, GetCertificateTool, InstallCertificateTool, ListCertificatesTool, ObtainLetsEncryptCertificateTool};
use App\Integrations\Forge\Data\Certificates\{CertificateCollectionData, CertificateData, ObtainLetsEncryptCertificateData};

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

function createMockCertificate(int $id = 1, string $domain = 'example.com', string $type = 'letsencrypt'): CertificateData
{
    return CertificateData::from([
        'id' => $id,
        'server_id' => 1,
        'site_id' => 1,
        'domain' => $domain,
        'request_status' => 'created',
        'status' => 'installed',
        'type' => $type,
        'active' => true,
        'existing' => false,
        'expires_at' => '2025-01-01T00:00:00Z',
        'created_at' => '2024-01-01T00:00:00Z',
        'activation_error' => null,
    ]);
}

describe('ListCertificatesTool', function (): void {
    it('requires server_id and site_id parameters', function (): void {
        $response = ForgeServer::tool(ListCertificatesTool::class, []);

        $response->assertHasErrors();
    });

    it('lists certificates successfully', function (): void {
        $mockCert = createMockCertificate();
        $collection = new CertificateCollectionData(certificates: [$mockCert]);

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('list')->with(1, 1)->once()->andReturn($collection);
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ListCertificatesTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('example.com');
    });

    it('returns empty list when no certificates exist', function (): void {
        $collection = new CertificateCollectionData(certificates: []);

        $this->mock(ForgeClient::class, function ($mock) use ($collection): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('list')->once()->andReturn($collection);
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ListCertificatesTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertOk()->assertSee('"count": 0');
    });
});

describe('GetCertificateTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(GetCertificateTool::class, []);

        $response->assertHasErrors();
    });

    it('gets certificate details successfully', function (): void {
        $mockCert = createMockCertificate();

        $this->mock(ForgeClient::class, function ($mock) use ($mockCert): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('get')->with(1, 1, 1)->once()->andReturn($mockCert);
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(GetCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'certificate_id' => 1,
        ]);

        $response->assertOk()->assertSee('example.com');
    });

    it('handles certificate not found', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('get')->andThrow(new Exception('Certificate not found'));
            $mock->shouldReceive('certificates')->andReturn($certResource);
        });

        $response = ForgeServer::tool(GetCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'certificate_id' => 999,
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('ObtainLetsEncryptCertificateTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, []);

        $response->assertHasErrors();
    });

    it('requires domains parameter', function (): void {
        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('obtains certificate successfully', function (): void {
        $mockCert = CertificateData::from([
            'id' => 1,
            'server_id' => 1,
            'site_id' => 1,
            'domain' => 'example.com',
            'request_status' => 'creating',
            'status' => 'installing',
            'type' => 'letsencrypt',
            'active' => false,
            'existing' => false,
            'expires_at' => null,
            'created_at' => '2024-01-01T00:00:00Z',
            'activation_error' => null,
        ]);

        $this->mock(ForgeClient::class, function ($mock) use ($mockCert): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('obtainLetsEncrypt')
                ->with(1, 1, Mockery::type(ObtainLetsEncryptCertificateData::class))
                ->once()
                ->andReturn($mockCert);
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'domains' => ['example.com'],
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('obtains certificate for multiple domains', function (): void {
        $mockCert = createMockCertificate();

        $this->mock(ForgeClient::class, function ($mock) use ($mockCert): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('obtainLetsEncrypt')->once()->andReturn($mockCert);
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'domains' => ['example.com', 'www.example.com'],
        ]);

        $response->assertOk();
    });

    it('handles DNS validation error', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('obtainLetsEncrypt')
                ->andThrow(new Exception('DNS validation failed'));
            $mock->shouldReceive('certificates')->andReturn($certResource);
        });

        $response = ForgeServer::tool(ObtainLetsEncryptCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'domains' => ['invalid.example.com'],
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('InstallCertificateTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(InstallCertificateTool::class, []);

        $response->assertHasErrors();
    });

    it('requires certificate_id parameter', function (): void {
        $response = ForgeServer::tool(InstallCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
        ]);

        $response->assertHasErrors();
    });

    it('installs certificate successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('activate')
                ->with(1, 1, 1)
                ->once();
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(InstallCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'certificate_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('ActivateCertificateTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(ActivateCertificateTool::class, []);

        $response->assertHasErrors();
    });

    it('activates certificate successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('activate')->with(1, 1, 1)->once();
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(ActivateCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'certificate_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });
});

describe('GetCertificateSigningRequestTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(GetCertificateSigningRequestTool::class, []);

        $response->assertHasErrors();
    });

    it('gets CSR successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('signingRequest')
                ->with(1, 1, 1)
                ->once()
                ->andReturn('-----BEGIN CERTIFICATE REQUEST-----...');
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(GetCertificateSigningRequestTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'certificate_id' => 1,
        ]);

        $response->assertOk()->assertSee('CERTIFICATE REQUEST');
    });
});

describe('DeleteCertificateTool', function (): void {
    it('requires all mandatory parameters', function (): void {
        $response = ForgeServer::tool(DeleteCertificateTool::class, []);

        $response->assertHasErrors();
    });

    it('deletes certificate successfully', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('delete')->with(1, 1, 1)->once();
            $mock->shouldReceive('certificates')->once()->andReturn($certResource);
        });

        $response = ForgeServer::tool(DeleteCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'certificate_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": true');
    });

    it('handles delete error', function (): void {
        $this->mock(ForgeClient::class, function ($mock): void {
            $certResource = Mockery::mock(CertificateResource::class);
            $certResource->shouldReceive('delete')->andThrow(new Exception('Cannot delete active certificate'));
            $mock->shouldReceive('certificates')->andReturn($certResource);
        });

        $response = ForgeServer::tool(DeleteCertificateTool::class, [
            'server_id' => 1,
            'site_id' => 1,
            'certificate_id' => 1,
        ]);

        $response->assertOk()->assertSee('"success": false');
    });
});

describe('Certificate Tools Structure', function (): void {
    it('all certificate tools can be instantiated', function (): void {
        $tools = [
            ListCertificatesTool::class,
            GetCertificateTool::class,
            ObtainLetsEncryptCertificateTool::class,
            InstallCertificateTool::class,
            ActivateCertificateTool::class,
            GetCertificateSigningRequestTool::class,
            DeleteCertificateTool::class,
        ];

        foreach ($tools as $toolClass) {
            $tool = app($toolClass);
            expect($tool->name())->toBeString()->not->toBeEmpty();
            expect($tool->description())->toBeString()->not->toBeEmpty();
        }
    });
});
