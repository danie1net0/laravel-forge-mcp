<?php

declare(strict_types=1);

use App\Mcp\Prompts\{DeployLaravelAppPrompt, MigrateSitePrompt, SSLRenewalPrompt, SetupLaravelSitePrompt, SetupNewServerPrompt, TroubleshootDeploymentPrompt};
use App\Mcp\Resources\{DeploymentBestPracticesResource, DeploymentGuidelinesResource, ForgeApiDocsResource, NginxOptimizationResource, PHPUpgradeGuideResource, QueueWorkerGuideResource, SecurityBestPracticesResource, SecurityHardeningResource, TroubleshootingGuideResource};
use App\Mcp\Servers\ForgeServer;

describe('Resources validation', function (): void {
    it('forge api docs resource contains API documentation', function (): void {
        $response = ForgeServer::resource(ForgeApiDocsResource::class);

        $response
            ->assertOk()
            ->assertSee('GET /servers')
            ->assertSee('POST /servers')
            ->assertSee('sites')
            ->assertSee('certificates');
    });

    it('deployment guidelines resource contains deployment steps', function (): void {
        $response = ForgeServer::resource(DeploymentGuidelinesResource::class);

        $response
            ->assertOk()
            ->assertSee('Environment Configuration')
            ->assertSee('Zero-Downtime')
            ->assertSee('Rollback');
    });

    it('deployment best practices resource contains recommendations', function (): void {
        $response = ForgeServer::resource(DeploymentBestPracticesResource::class);

        $response
            ->assertOk()
            ->assertSee('deployment')
            ->assertSee('cache')
            ->assertSee('migration');
    });

    it('security best practices resource contains security guidelines', function (): void {
        $response = ForgeServer::resource(SecurityBestPracticesResource::class);

        $response
            ->assertOk()
            ->assertSee('HTTPS')
            ->assertSee('Firewall')
            ->assertSee('SSH');
    });

    it('troubleshooting guide resource contains common issues', function (): void {
        $response = ForgeServer::resource(TroubleshootingGuideResource::class);

        $response
            ->assertOk()
            ->assertSee('error')
            ->assertSee('debug');
    });

    it('php upgrade guide resource contains upgrade procedures', function (): void {
        $response = ForgeServer::resource(PHPUpgradeGuideResource::class);

        $response
            ->assertOk()
            ->assertSee('PHP')
            ->assertSee('upgrade')
            ->assertSee('Laravel')
            ->assertSee('OPcache');
    });

    it('queue worker guide resource contains worker configuration', function (): void {
        $response = ForgeServer::resource(QueueWorkerGuideResource::class);

        $response
            ->assertOk()
            ->assertSee('Queue')
            ->assertSee('Worker')
            ->assertSee('Redis')
            ->assertSee('Horizon');
    });

    it('nginx optimization resource contains performance tuning', function (): void {
        $response = ForgeServer::resource(NginxOptimizationResource::class);

        $response
            ->assertOk()
            ->assertSee('Nginx')
            ->assertSee('gzip')
            ->assertSee('cache')
            ->assertSee('FastCGI');
    });

    it('security hardening resource contains advanced security', function (): void {
        $response = ForgeServer::resource(SecurityHardeningResource::class);

        $response
            ->assertOk()
            ->assertSee('SSH')
            ->assertSee('Firewall')
            ->assertSee('Fail2Ban')
            ->assertSee('Intrusion');
    });
});

describe('Prompts validation', function (): void {
    it('deploy laravel app prompt returns workflow steps', function (): void {
        $response = ForgeServer::prompt(DeployLaravelAppPrompt::class, []);

        $response
            ->assertOk()
            ->assertSee('Deployment')
            ->assertSee('deploy-site-tool');
    });

    it('setup new server prompt returns provisioning workflow', function (): void {
        $response = ForgeServer::prompt(SetupNewServerPrompt::class, []);

        $response
            ->assertOk()
            ->assertSee('Server')
            ->assertSee('create-server-tool')
            ->assertSee('firewall');
    });

    it('migrate site prompt returns migration workflow', function (): void {
        $response = ForgeServer::prompt(MigrateSitePrompt::class, []);

        $response
            ->assertOk()
            ->assertSee('Migration')
            ->assertSee('source')
            ->assertSee('target')
            ->assertSee('DNS');
    });

    it('troubleshoot deployment prompt returns diagnostic steps', function (): void {
        $response = ForgeServer::prompt(TroubleshootDeploymentPrompt::class, []);

        $response
            ->assertOk()
            ->assertSee('Troubleshoot')
            ->assertSee('error')
            ->assertSee('Composer')
            ->assertSee('npm');
    });

    it('ssl renewal prompt returns certificate workflow', function (): void {
        $response = ForgeServer::prompt(SSLRenewalPrompt::class, []);

        $response
            ->assertOk()
            ->assertSee('SSL')
            ->assertSee('Certificate')
            ->assertSee("Let's Encrypt");
    });

    it('setup laravel site prompt returns complete setup workflow', function (): void {
        $response = ForgeServer::prompt(SetupLaravelSitePrompt::class, []);

        $response
            ->assertOk()
            ->assertSee('Laravel')
            ->assertSee('create-site-tool')
            ->assertSee('deploy-site-tool')
            ->assertSee('worker');
    });

    it('prompts accept optional parameters', function (): void {
        $response = ForgeServer::prompt(SetupNewServerPrompt::class, [
            'provider' => 'digitalocean',
            'region' => 'nyc1',
        ]);

        $response->assertOk();
    });

    it('troubleshoot deployment prompt handles error type parameter', function (): void {
        $response = ForgeServer::prompt(TroubleshootDeploymentPrompt::class, [
            'error_type' => 'composer',
        ]);

        $response
            ->assertOk()
            ->assertSee('composer');
    });
});

describe('Resources and Prompts structure', function (): void {
    it('all resources have required properties', function (): void {
        $resourceClasses = [
            ForgeApiDocsResource::class,
            DeploymentGuidelinesResource::class,
            DeploymentBestPracticesResource::class,
            SecurityBestPracticesResource::class,
            TroubleshootingGuideResource::class,
            PHPUpgradeGuideResource::class,
            QueueWorkerGuideResource::class,
            NginxOptimizationResource::class,
            SecurityHardeningResource::class,
        ];

        foreach ($resourceClasses as $className) {
            $resource = app($className);

            expect(method_exists($resource, 'uri') || property_exists($resource, 'uri'))->toBeTrue();
            expect(method_exists($resource, 'name') || property_exists($resource, 'name'))->toBeTrue();
            expect(method_exists($resource, 'description') || property_exists($resource, 'description'))->toBeTrue();
            expect(method_exists($resource, 'handle'))->toBeTrue();
        }
    });

    it('all prompts have required methods', function (): void {
        $promptClasses = [
            DeployLaravelAppPrompt::class,
            SetupNewServerPrompt::class,
            MigrateSitePrompt::class,
            TroubleshootDeploymentPrompt::class,
            SSLRenewalPrompt::class,
            SetupLaravelSitePrompt::class,
        ];

        foreach ($promptClasses as $className) {
            $prompt = app($className);

            expect($prompt->name())->toBeString()->not->toBeEmpty();
            expect($prompt->description())->toBeString()->not->toBeEmpty();
            expect($prompt->arguments())->toBeArray();
        }
    });

    it('validates we have exactly 9 resources', function (): void {
        $resourcesPath = app_path('Mcp/Resources');
        $resourceFiles = collect(File::allFiles($resourcesPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Resource.php'))
            ->values();

        expect($resourceFiles)->toHaveCount(9, 'Expected exactly 9 resources');
    });

    it('validates we have exactly 6 prompts', function (): void {
        $promptsPath = app_path('Mcp/Prompts');
        $promptFiles = collect(File::allFiles($promptsPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Prompt.php'))
            ->values();

        expect($promptFiles)->toHaveCount(6, 'Expected exactly 6 prompts');
    });
});
