<?php

declare(strict_types=1);

use App\Integrations\Forge\Requests\Sites\{GetSiteRequest, ListSitesRequest};
use App\Integrations\Forge\Requests\Servers\{GetServerRequest, ListServersRequest};

describe('Saloon Requests Structure', function (): void {
    it('validates all Requests have createDtoFromResponse method', function (): void {
        $requestsPath = app_path('Integrations/Forge/Requests');
        $requestFiles = collect(File::allFiles($requestsPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Request.php'))
            ->values();

        expect($requestFiles->count())->toBeGreaterThan(130);

        $failures = [];

        foreach ($requestFiles as $file) {
            $relativePath = str_replace(
                [app_path('Integrations/Forge/Requests/'), '.php', '/'],
                ['', '', '\\'],
                $file->getPathname()
            );
            $className = "App\\Integrations\\Forge\\Requests\\{$relativePath}";

            if (! class_exists($className)) {
                $failures[] = "{$className} class not found";

                continue;
            }

            $reflection = new ReflectionClass($className);

            if (! $reflection->hasMethod('createDtoFromResponse')) {
                $failures[] = "{$className} missing createDtoFromResponse method";
            }

            if (! $reflection->hasMethod('resolveEndpoint')) {
                $failures[] = "{$className} missing resolveEndpoint method";
            }
        }

        expect($failures)->toBeEmpty(
            "The following requests had issues:\n" . implode("\n", $failures)
        );
    });

    it('validates all Requests extend SaloonRequest', function (): void {
        $requestsPath = app_path('Integrations/Forge/Requests');
        $requestFiles = collect(File::allFiles($requestsPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Request.php'))
            ->values();

        $failures = [];

        foreach ($requestFiles as $file) {
            $relativePath = str_replace(
                [app_path('Integrations/Forge/Requests/'), '.php', '/'],
                ['', '', '\\'],
                $file->getPathname()
            );
            $className = "App\\Integrations\\Forge\\Requests\\{$relativePath}";

            if (! class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);
            $parentClass = $reflection->getParentClass();

            if (! $parentClass || $parentClass->getName() !== 'Saloon\Http\Request') {
                $failures[] = "{$className} does not extend Saloon\\Http\\Request";
            }
        }

        expect($failures)->toBeEmpty(
            "The following requests have wrong inheritance:\n" . implode("\n", $failures)
        );
    });

    it('validates Requests have correct HTTP methods', function (): void {
        $requestsPath = app_path('Integrations/Forge/Requests');
        $requestFiles = collect(File::allFiles($requestsPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Request.php'))
            ->values();

        foreach ($requestFiles as $file) {
            $relativePath = str_replace(
                [app_path('Integrations/Forge/Requests/'), '.php', '/'],
                ['', '', '\\'],
                $file->getPathname()
            );
            $className = "App\\Integrations\\Forge\\Requests\\{$relativePath}";

            if (! class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if ($reflection->hasProperty('method')) {
                $property = $reflection->getProperty('method');
                $property->setAccessible(true);

                $filename = mb_strtolower($file->getFilename());

                if (str_starts_with($filename, 'list') || str_starts_with($filename, 'get')) {
                    expect($property->getDefaultValue()->value)->toBe('GET');
                } elseif (str_starts_with($filename, 'create') || str_starts_with($filename, 'install') || str_starts_with($filename, 'obtain') || str_starts_with($filename, 'enable') || str_starts_with($filename, 'run') || str_starts_with($filename, 'deploy') || str_starts_with($filename, 'execute') || str_starts_with($filename, 'restore') || str_starts_with($filename, 'sync') || str_starts_with($filename, 'reboot') || str_starts_with($filename, 'restart') || str_starts_with($filename, 'stop') || str_starts_with($filename, 'start')) {
                    expect(in_array($property->getDefaultValue()->value, ['POST', 'PUT'], true))->toBeTrue();
                } elseif (str_starts_with($filename, 'update') || str_starts_with($filename, 'change') || str_starts_with($filename, 'set')) {
                    expect(in_array($property->getDefaultValue()->value, ['PUT', 'POST'], true))->toBeTrue();
                } elseif (str_starts_with($filename, 'delete') || str_starts_with($filename, 'destroy') || str_starts_with($filename, 'remove') || str_starts_with($filename, 'revoke') || str_starts_with($filename, 'uninstall') || str_starts_with($filename, 'disable') || str_starts_with($filename, 'clear')) {
                    expect(in_array($property->getDefaultValue()->value, ['DELETE', 'POST'], true))->toBeTrue();
                }
            }
        }
    });
});

describe('ListServersRequest', function (): void {
    it('resolves correct endpoint', function (): void {
        $request = new ListServersRequest();

        expect($request->resolveEndpoint())->toBe('/servers');
    });

    it('has createDtoFromResponse method', function (): void {
        $request = new ListServersRequest();
        $reflection = new ReflectionClass($request);

        expect($reflection->hasMethod('createDtoFromResponse'))->toBeTrue();
    });
});

describe('GetServerRequest', function (): void {
    it('resolves correct endpoint', function (): void {
        $request = new GetServerRequest(123);

        expect($request->resolveEndpoint())->toBe('/servers/123');
    });

    it('has createDtoFromResponse method', function (): void {
        $request = new GetServerRequest(123);
        $reflection = new ReflectionClass($request);

        expect($reflection->hasMethod('createDtoFromResponse'))->toBeTrue();
    });
});

describe('ListSitesRequest', function (): void {
    it('resolves correct endpoint', function (): void {
        $request = new ListSitesRequest(123);

        expect($request->resolveEndpoint())->toBe('/servers/123/sites');
    });
});

describe('GetSiteRequest', function (): void {
    it('resolves correct endpoint', function (): void {
        $request = new GetSiteRequest(123, 456);

        expect($request->resolveEndpoint())->toBe('/servers/123/sites/456');
    });

    it('has createDtoFromResponse method', function (): void {
        $request = new GetSiteRequest(123, 456);
        $reflection = new ReflectionClass($request);

        expect($reflection->hasMethod('createDtoFromResponse'))->toBeTrue();
    });
});

describe('Request Categories Coverage', function (): void {
    $categories = [
        'Backups' => ['ListBackupConfigurations', 'GetBackupConfiguration', 'CreateBackupConfiguration', 'UpdateBackupConfiguration', 'DeleteBackupConfiguration', 'RestoreBackup', 'DeleteBackup'],
        'Certificates' => ['ListCertificates', 'GetCertificate', 'ObtainLetsEncryptCertificate', 'GetSigningRequest', 'DeleteCertificate', 'ActivateCertificate'],
        'Credentials' => ['ListCredentials'],
        'Daemons' => ['ListDaemons', 'GetDaemon', 'CreateDaemon', 'RestartDaemon', 'DeleteDaemon'],
        'Databases' => ['ListDatabases', 'GetDatabase', 'CreateDatabase', 'DeleteDatabase', 'ListDatabaseUsers', 'GetDatabaseUser', 'CreateDatabaseUser', 'UpdateDatabaseUser', 'DeleteDatabaseUser', 'SyncDatabase'],
        'Firewall' => ['ListFirewallRules', 'GetFirewallRule', 'CreateFirewallRule', 'DeleteFirewallRule'],
        'Integrations' => ['EnableMaintenance', 'EnableOctane'],
        'Jobs' => ['ListJobs', 'GetJob', 'CreateJob', 'DeleteJob', 'GetJobOutput'],
        'Monitors' => ['ListMonitors', 'GetMonitor', 'CreateMonitor', 'DeleteMonitor'],
        'NginxTemplates' => ['ListNginxTemplates', 'GetNginxTemplate', 'GetNginxDefaultTemplate', 'CreateNginxTemplate', 'UpdateNginxTemplate', 'DeleteNginxTemplate'],
        'Php' => ['InstallPhp', 'UpdatePhp'],
        'Recipes' => ['ListRecipes', 'GetRecipe', 'CreateRecipe', 'UpdateRecipe', 'DeleteRecipe', 'RunRecipe'],
        'RedirectRules' => ['ListRedirectRules', 'GetRedirectRule', 'CreateRedirectRule', 'DeleteRedirectRule'],
        'SecurityRules' => ['ListSecurityRules', 'GetSecurityRule', 'CreateSecurityRule', 'DeleteSecurityRule'],
        'Servers' => ['ListServers', 'GetServer', 'CreateServer', 'UpdateServer', 'DeleteServer', 'RebootServer', 'ListEvents', 'GetEventOutput', 'GetServerLog', 'ReactivateServer', 'ReconnectServer', 'RevokeServerAccess', 'UpdateDatabasePassword'],
        'Services' => ['InstallBlackfire', 'InstallPapertrail', 'ManageService'],
        'Sites' => ['ListSites', 'GetSite', 'CreateSite', 'UpdateSite', 'DeleteSite', 'GetSiteLog', 'ClearSiteLog', 'ChangePhpVersion', 'DeploySite', 'GetDeploymentLog', 'GetDeploymentScript', 'UpdateDeploymentScript', 'EnableQuickDeploy', 'DisableQuickDeploy', 'GetDeploymentHistory', 'GetDeploymentHistoryDeployment', 'GetDeploymentHistoryOutput', 'ResetDeploymentState', 'InstallGitRepository', 'UpdateGitRepository', 'DestroyGitRepository', 'CreateDeployKey', 'DeleteDeployKey', 'GetEnvFile', 'UpdateEnvFile', 'GetNginxConfig', 'UpdateNginxConfig', 'ExecuteSiteCommand', 'GetSiteCommand', 'ListCommandHistory', 'InstallWordPress', 'UninstallWordPress', 'InstallPhpMyAdmin', 'UninstallPhpMyAdmin', 'GetPackagesAuth', 'UpdatePackagesAuth', 'SetDeploymentFailureEmails', 'ListAliases', 'UpdateAliases', 'GetLoadBalancing', 'UpdateLoadBalancing'],
        'SSHKeys' => ['ListSSHKeys', 'GetSSHKey', 'CreateSSHKey', 'DeleteSSHKey'],
        'User' => ['GetUser'],
        'Webhooks' => ['ListWebhooks', 'GetWebhook', 'CreateWebhook', 'DeleteWebhook'],
        'Workers' => ['ListWorkers', 'GetWorker', 'CreateWorker', 'RestartWorker', 'DeleteWorker', 'GetWorkerOutput'],
    ];

    foreach ($categories as $category => $requests) {
        it("validates all {$category} requests exist", function () use ($category, $requests): void {
            foreach ($requests as $requestName) {
                $className = "App\\Integrations\\Forge\\Requests\\{$category}\\{$requestName}Request";
                expect(class_exists($className))->toBeTrue("Request {$className} should exist");
            }
        });
    }
});
