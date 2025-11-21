<?php

declare(strict_types=1);

use App\Services\ForgeService;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\{Certificate, Daemon, Database, FirewallRule, Job, Server, Site};

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

describe('ForgeService initialization', function (): void {
    it('throws exception when API token is not configured', function (): void {
        config(['services.forge.api_token' => null]);

        expect(fn () => new ForgeService())
            ->toThrow(RuntimeException::class, 'Forge API token not configured');
    });

    it('initializes successfully with valid token', function (): void {
        $service = new ForgeService();

        expect($service)->toBeInstanceOf(ForgeService::class);
    });

    it('returns forge instance', function (): void {
        $service = new ForgeService();

        expect($service->getForgeInstance())->toBeInstanceOf(Forge::class);
    });
});

describe('ForgeService server methods', function (): void {
    it('listServers returns array', function (): void {
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('servers')->once()->andReturn([]);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->listServers())->toBeArray();
    });

    it('getServer returns Server', function (): void {
        $server = new Server(['id' => 1, 'name' => 'test']);
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('server')->with(1)->once()->andReturn($server);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->getServer(1))->toBeInstanceOf(Server::class);
    });

    it('rebootServer calls forge rebootServer', function (): void {
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('rebootServer')->with(1)->once();

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        $service->rebootServer(1);

        expect(true)->toBeTrue();
    });
});

describe('ForgeService site methods', function (): void {
    it('listSites returns array', function (): void {
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('sites')->with(1)->once()->andReturn([]);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->listSites(1))->toBeArray();
    });

    it('getSite returns Site', function (): void {
        $site = new Site(['id' => 1, 'name' => 'test.com']);
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('site')->with(1, 1)->once()->andReturn($site);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->getSite(1, 1))->toBeInstanceOf(Site::class);
    });

    it('deploySite calls forge deploySite', function (): void {
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('deploySite')->with(1, 1)->once();

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        $service->deploySite(1, 1);

        expect(true)->toBeTrue();
    });
});

describe('ForgeService deployment methods', function (): void {
    it('getSiteDeploymentScript returns string', function (): void {
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('siteDeploymentScript')->with(1, 1)->once()->andReturn('script');

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->getSiteDeploymentScript(1, 1))->toBe('script');
    });

    it('siteDeploymentLog returns nullable string', function (): void {
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('siteDeploymentLog')->with(1, 1)->once()->andReturn('log content');

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->siteDeploymentLog(1, 1))->toBe('log content');
    });

    it('siteDeploymentLog returns null when no log', function (): void {
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('siteDeploymentLog')->with(1, 1)->once()->andReturn(null);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->siteDeploymentLog(1, 1))->toBeNull();
    });
});

describe('ForgeService certificate methods', function (): void {
    it('listCertificates returns array', function (): void {
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('certificates')->with(1, 1)->once()->andReturn([]);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->listCertificates(1, 1))->toBeArray();
    });

    it('obtainLetsEncryptCertificate returns Certificate', function (): void {
        $cert = new Certificate(['id' => 1, 'domain' => 'test.com']);
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('obtainLetsEncryptCertificate')->with(1, 1, ['domains' => ['test.com']])->once()->andReturn($cert);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->obtainLetsEncryptCertificate(1, 1, ['domains' => ['test.com']]))->toBeInstanceOf(Certificate::class);
    });
});

describe('ForgeService database methods', function (): void {
    it('listDatabases returns array', function (): void {
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('databases')->with(1)->once()->andReturn([]);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->listDatabases(1))->toBeArray();
    });

    it('getDatabase returns Database', function (): void {
        $db = new Database(['id' => 1, 'name' => 'forge']);
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('database')->with(1, 1)->once()->andReturn($db);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->getDatabase(1, 1))->toBeInstanceOf(Database::class);
    });
});

describe('ForgeService job methods', function (): void {
    it('listJobs returns array', function (): void {
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('jobs')->with(1)->once()->andReturn([]);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->listJobs(1))->toBeArray();
    });

    it('getJob returns Job', function (): void {
        $job = new Job(['id' => 1, 'command' => 'test']);
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('job')->with(1, 1)->once()->andReturn($job);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->getJob(1, 1))->toBeInstanceOf(Job::class);
    });
});

describe('ForgeService daemon methods', function (): void {
    it('listDaemons returns array', function (): void {
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('daemons')->with(1)->once()->andReturn([]);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->listDaemons(1))->toBeArray();
    });

    it('getDaemon returns Daemon', function (): void {
        $daemon = new Daemon(['id' => 1, 'command' => 'horizon']);
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('daemon')->with(1, 1)->once()->andReturn($daemon);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->getDaemon(1, 1))->toBeInstanceOf(Daemon::class);
    });
});

describe('ForgeService firewall methods', function (): void {
    it('listFirewallRules returns array', function (): void {
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('firewallRules')->with(1)->once()->andReturn([]);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->listFirewallRules(1))->toBeArray();
    });

    it('getFirewallRule returns FirewallRule', function (): void {
        $rule = new FirewallRule(['id' => 1, 'name' => 'SSH']);
        $forge = Mockery::mock(Forge::class);
        $forge->shouldReceive('firewallRule')->with(1, 1)->once()->andReturn($rule);

        $service = new ForgeService();
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('forge');
        $property->setValue($service, $forge);

        expect($service->getFirewallRule(1, 1))->toBeInstanceOf(FirewallRule::class);
    });
});
