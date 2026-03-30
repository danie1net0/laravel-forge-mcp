<?php

declare(strict_types=1);

use App\Integrations\Forge\Data\Daemons\CreateDaemonData;
use App\Integrations\Forge\Data\Firewall\CreateFirewallRuleData;
use App\Integrations\Forge\Data\Jobs\CreateJobData;
use App\Integrations\Forge\Data\Monitors\CreateMonitorData;
use App\Integrations\Forge\Data\RedirectRules\CreateRedirectRuleData;
use App\Integrations\Forge\Data\SecurityRules\CreateSecurityRuleData;
use App\Integrations\Forge\Data\Sites\{CreateSiteData, ExecuteSiteCommandData, InstallGitRepositoryData, UpdateGitRepositoryData, UpdateSiteData};
use App\Integrations\Forge\Data\Backups\{CreateBackupConfigurationData, UpdateBackupConfigurationData};
use App\Integrations\Forge\Data\Servers\{CreateServerData, UpdateServerData};
use App\Integrations\Forge\Requests\Php\{InstallPhpRequest, UpdatePhpRequest};
use App\Integrations\Forge\Requests\Jobs\{CreateJobRequest, DeleteJobRequest, GetJobOutputRequest, GetJobRequest, ListJobsRequest};
use App\Integrations\Forge\Data\Databases\{CreateDatabaseData, CreateDatabaseUserData, UpdateDatabaseUserData};
use App\Integrations\Forge\Requests\Sites\{ChangePhpVersionRequest, ClearSiteLogRequest, CreateDeployKeyRequest, CreateSiteRequest, DeleteDeployKeyRequest, DeleteSiteRequest, DeploySiteRequest, DestroyGitRepositoryRequest, DisableQuickDeployRequest, EnableQuickDeployRequest, ExecuteSiteCommandRequest, GetDeploymentHistoryDeploymentRequest, GetDeploymentHistoryOutputRequest, GetDeploymentHistoryRequest, GetDeploymentLogRequest, GetDeploymentScriptRequest, GetEnvFileRequest, GetLoadBalancingRequest, GetNginxConfigRequest, GetPackagesAuthRequest, GetSiteCommandRequest, GetSiteLogRequest, GetSiteRequest, InstallGitRepositoryRequest, InstallPhpMyAdminRequest, InstallWordPressRequest, ListAliasesRequest, ListCommandHistoryRequest, ListSitesRequest, ResetDeploymentStateRequest, SetDeploymentFailureEmailsRequest, UninstallPhpMyAdminRequest, UninstallWordPressRequest, UpdateAliasesRequest, UpdateDeploymentScriptRequest, UpdateEnvFileRequest, UpdateGitRepositoryRequest, UpdateLoadBalancingRequest, UpdateNginxConfigRequest, UpdatePackagesAuthRequest, UpdateSiteRequest};
use App\Integrations\Forge\Requests\Backups\{CreateBackupConfigurationRequest, DeleteBackupConfigurationRequest, DeleteBackupRequest, GetBackupConfigurationRequest, ListBackupConfigurationsRequest, RestoreBackupRequest, UpdateBackupConfigurationRequest};
use App\Integrations\Forge\Requests\Daemons\{CreateDaemonRequest, DeleteDaemonRequest, GetDaemonRequest, ListDaemonsRequest, RestartDaemonRequest};
use App\Integrations\Forge\Requests\SSHKeys\{CreateSSHKeyRequest, DeleteSSHKeyRequest, GetSSHKeyRequest, ListSSHKeysRequest};
use App\Integrations\Forge\Requests\Servers\{CreateServerRequest, DeleteServerRequest, GetEventOutputRequest, GetServerLogRequest, GetServerRequest, ListEventsRequest, ListServersRequest, ReactivateServerRequest, RebootServerRequest, ReconnectServerRequest, RevokeServerAccessRequest, UpdateDatabasePasswordRequest, UpdateServerRequest};
use App\Integrations\Forge\Requests\Workers\{CreateWorkerRequest, DeleteWorkerRequest, GetWorkerOutputRequest, GetWorkerRequest, ListWorkersRequest, RestartWorkerRequest};
use App\Integrations\Forge\Requests\Firewall\{CreateFirewallRuleRequest, DeleteFirewallRuleRequest, GetFirewallRuleRequest, ListFirewallRulesRequest};
use App\Integrations\Forge\Requests\Monitors\{CreateMonitorRequest, DeleteMonitorRequest, GetMonitorRequest, ListMonitorsRequest};
use App\Integrations\Forge\Requests\Services\{InstallBlackfireRequest, InstallPapertrailRequest, ManageServiceRequest};
use App\Integrations\Forge\Requests\Webhooks\{CreateWebhookRequest, DeleteWebhookRequest, GetWebhookRequest, ListWebhooksRequest};
use App\Integrations\Forge\Requests\Databases\{CreateDatabaseRequest, CreateDatabaseUserRequest, DeleteDatabaseRequest, DeleteDatabaseUserRequest, GetDatabaseRequest, GetDatabaseUserRequest, ListDatabaseUsersRequest, ListDatabasesRequest, SyncDatabaseRequest, UpdateDatabaseUserRequest};
use App\Integrations\Forge\Data\NginxTemplates\{CreateNginxTemplateData, UpdateNginxTemplateData};
use App\Integrations\Forge\Requests\Certificates\{ActivateCertificateRequest, DeleteCertificateRequest, GetCertificateRequest, GetSigningRequestRequest, ListCertificatesRequest, ObtainLetsEncryptCertificateRequest};
use App\Integrations\Forge\Requests\Integrations\{EnableMaintenanceRequest, EnableOctaneRequest};
use App\Integrations\Forge\Requests\RedirectRules\{CreateRedirectRuleRequest, DeleteRedirectRuleRequest, GetRedirectRuleRequest, ListRedirectRulesRequest};
use App\Integrations\Forge\Requests\SecurityRules\{CreateSecurityRuleRequest, DeleteSecurityRuleRequest, GetSecurityRuleRequest, ListSecurityRulesRequest};
use App\Integrations\Forge\Requests\NginxTemplates\{CreateNginxTemplateRequest, DeleteNginxTemplateRequest, GetNginxDefaultTemplateRequest, GetNginxTemplateRequest, ListNginxTemplatesRequest, UpdateNginxTemplateRequest};
use App\Integrations\Forge\Data\SSHKeys\CreateSSHKeyData;
use App\Integrations\Forge\Data\Webhooks\CreateWebhookData;
use App\Integrations\Forge\Data\Workers\CreateWorkerData;
use App\Integrations\Forge\Requests\User\GetUserRequest;

function getHttpMethod(object $request): string
{
    $reflection = new ReflectionClass($request);
    $property = $reflection->getProperty('method');

    return $property->getDefaultValue()->value;
}

/**
 * @return array<string, string|int>
 */
function getDefaultQuery(object $request): array
{
    $reflection = new ReflectionClass($request);
    $method = $reflection->getMethod('defaultQuery');
    $method->setAccessible(true);

    return $method->invoke($request);
}

/**
 * @return array<string, mixed>
 */
function getDefaultBody(object $request): array
{
    $reflection = new ReflectionClass($request);
    $method = $reflection->getMethod('defaultBody');
    $method->setAccessible(true);

    return $method->invoke($request);
}

describe('Backups', function (): void {
    it('resolves ListBackupConfigurationsRequest endpoint and method', function (): void {
        $request = new ListBackupConfigurationsRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/backups')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetBackupConfigurationRequest endpoint and method', function (): void {
        $request = new GetBackupConfigurationRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/backups/2')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateBackupConfigurationRequest endpoint and method', function (): void {
        $data = CreateBackupConfigurationData::from(['provider' => 's3']);
        $request = new CreateBackupConfigurationRequest(1, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/backups')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateBackupConfigurationRequest body', function (): void {
        $data = CreateBackupConfigurationData::from([
            'provider' => 's3',
            'providerName' => 'my-backup',
            'dayOfWeek' => '1',
            'time' => '12:00',
        ]);
        $request = new CreateBackupConfigurationRequest(1, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('provider', 's3')
            ->toHaveKey('providerName', 'my-backup')
            ->toHaveKey('dayOfWeek', '1')
            ->toHaveKey('time', '12:00');
    });

    it('filters null values from CreateBackupConfigurationRequest body', function (): void {
        $data = CreateBackupConfigurationData::from(['provider' => 's3']);
        $request = new CreateBackupConfigurationRequest(1, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('provider', 's3')
            ->not->toHaveKey('dayOfWeek');
    });

    it('resolves UpdateBackupConfigurationRequest endpoint and method', function (): void {
        $data = UpdateBackupConfigurationData::from(['provider' => 's3']);
        $request = new UpdateBackupConfigurationRequest(1, 2, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/backups/2')
            ->and(getHttpMethod($request))->toBe('PUT');
    });

    it('builds UpdateBackupConfigurationRequest body', function (): void {
        $data = UpdateBackupConfigurationData::from(['provider' => 's3', 'time' => '14:00']);
        $request = new UpdateBackupConfigurationRequest(1, 2, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('provider', 's3')
            ->toHaveKey('time', '14:00');
    });

    it('resolves DeleteBackupConfigurationRequest endpoint and method', function (): void {
        $request = new DeleteBackupConfigurationRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/backups/2')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });

    it('resolves RestoreBackupRequest endpoint and method', function (): void {
        $request = new RestoreBackupRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/backups/2/instances/3/restores')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('resolves DeleteBackupRequest endpoint and method', function (): void {
        $request = new DeleteBackupRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/backups/2/instances/3')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });
});

describe('Certificates', function (): void {
    it('resolves ListCertificatesRequest endpoint and method', function (): void {
        $request = new ListCertificatesRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/domains')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetCertificateRequest endpoint and method', function (): void {
        $request = new GetCertificateRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/domains/3/certificate')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves ObtainLetsEncryptCertificateRequest endpoint and method', function (): void {
        $request = new ObtainLetsEncryptCertificateRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/domains/3/certificate/actions')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds ObtainLetsEncryptCertificateRequest body with enable action', function (): void {
        $request = new ObtainLetsEncryptCertificateRequest(1, 2, 3);
        $body = getDefaultBody($request);

        expect($body)->toHaveKey('action', 'enable');
    });

    it('resolves GetSigningRequestRequest endpoint and method', function (): void {
        $request = new GetSigningRequestRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/domains/3/certificate/csr')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves DeleteCertificateRequest endpoint and method', function (): void {
        $request = new DeleteCertificateRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/domains/3/certificate/actions')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds DeleteCertificateRequest body with disable action', function (): void {
        $request = new DeleteCertificateRequest(1, 2, 3);
        $body = getDefaultBody($request);

        expect($body)->toHaveKey('action', 'disable');
    });

    it('resolves ActivateCertificateRequest endpoint and method', function (): void {
        $request = new ActivateCertificateRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/domains/3/certificate/actions')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds ActivateCertificateRequest body with enable action', function (): void {
        $request = new ActivateCertificateRequest(1, 2, 3);
        $body = getDefaultBody($request);

        expect($body)->toHaveKey('action', 'enable');
    });
});

describe('Daemons', function (): void {
    it('resolves ListDaemonsRequest endpoint and method', function (): void {
        $request = new ListDaemonsRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/background-processes')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetDaemonRequest endpoint and method', function (): void {
        $request = new GetDaemonRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/background-processes/2')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateDaemonRequest endpoint and method', function (): void {
        $data = CreateDaemonData::from(['command' => 'php artisan queue:work', 'directory' => '/home/forge/app']);
        $request = new CreateDaemonRequest(1, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/background-processes')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateDaemonRequest body filtering nulls', function (): void {
        $data = CreateDaemonData::from(['command' => 'php artisan queue:work', 'directory' => '/home/forge/app']);
        $request = new CreateDaemonRequest(1, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('command', 'php artisan queue:work')
            ->toHaveKey('directory', '/home/forge/app')
            ->toHaveKey('user', 'forge')
            ->not->toHaveKey('processes');
    });

    it('resolves RestartDaemonRequest endpoint and method', function (): void {
        $request = new RestartDaemonRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/background-processes/2/actions')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('resolves DeleteDaemonRequest endpoint and method', function (): void {
        $request = new DeleteDaemonRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/background-processes/2')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });
});

describe('Databases', function (): void {
    it('resolves ListDatabasesRequest endpoint and method', function (): void {
        $request = new ListDatabasesRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/schemas')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetDatabaseRequest endpoint and method', function (): void {
        $request = new GetDatabaseRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/schemas/2')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateDatabaseRequest endpoint and method', function (): void {
        $data = CreateDatabaseData::from(['name' => 'forge_db']);
        $request = new CreateDatabaseRequest(1, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/schemas')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateDatabaseRequest body', function (): void {
        $data = CreateDatabaseData::from(['name' => 'forge_db', 'user' => 'forge_user', 'password' => 'secret']);
        $request = new CreateDatabaseRequest(1, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('name', 'forge_db')
            ->toHaveKey('user', 'forge_user')
            ->toHaveKey('password', 'secret');
    });

    it('resolves DeleteDatabaseRequest endpoint and method', function (): void {
        $request = new DeleteDatabaseRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/schemas/2')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });

    it('resolves ListDatabaseUsersRequest endpoint and method', function (): void {
        $request = new ListDatabaseUsersRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/users')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetDatabaseUserRequest endpoint and method', function (): void {
        $request = new GetDatabaseUserRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/users/2')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateDatabaseUserRequest endpoint and method', function (): void {
        $data = CreateDatabaseUserData::from(['name' => 'forge_user', 'password' => 'secret']);
        $request = new CreateDatabaseUserRequest(1, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/users')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateDatabaseUserRequest body', function (): void {
        $data = CreateDatabaseUserData::from(['name' => 'forge_user', 'password' => 'secret', 'databases' => [1, 2]]);
        $request = new CreateDatabaseUserRequest(1, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('name', 'forge_user')
            ->toHaveKey('password', 'secret')
            ->toHaveKey('databases', [1, 2]);
    });

    it('resolves UpdateDatabaseUserRequest endpoint and method', function (): void {
        $data = UpdateDatabaseUserData::from(['databases' => [1, 2]]);
        $request = new UpdateDatabaseUserRequest(1, 2, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/users/2')
            ->and(getHttpMethod($request))->toBe('PUT');
    });

    it('builds UpdateDatabaseUserRequest body', function (): void {
        $data = UpdateDatabaseUserData::from(['databases' => [3, 4]]);
        $request = new UpdateDatabaseUserRequest(1, 2, $data);
        $body = getDefaultBody($request);

        expect($body)->toHaveKey('databases', [3, 4]);
    });

    it('resolves DeleteDatabaseUserRequest endpoint and method', function (): void {
        $request = new DeleteDatabaseUserRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/users/2')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });

    it('resolves SyncDatabaseRequest endpoint and method', function (): void {
        $request = new SyncDatabaseRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/schemas/synchronizations')
            ->and(getHttpMethod($request))->toBe('POST');
    });
});

describe('Firewall', function (): void {
    it('resolves ListFirewallRulesRequest endpoint and method', function (): void {
        $request = new ListFirewallRulesRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/firewall-rules')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetFirewallRuleRequest endpoint and method', function (): void {
        $request = new GetFirewallRuleRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/firewall-rules/2')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateFirewallRuleRequest endpoint and method', function (): void {
        $data = CreateFirewallRuleData::from(['name' => 'SSH', 'port' => 22]);
        $request = new CreateFirewallRuleRequest(1, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/firewall-rules')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateFirewallRuleRequest body filtering nulls', function (): void {
        $data = CreateFirewallRuleData::from(['name' => 'SSH', 'port' => 22]);
        $request = new CreateFirewallRuleRequest(1, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('name', 'SSH')
            ->toHaveKey('port', 22)
            ->not->toHaveKey('ipAddress');
    });

    it('resolves DeleteFirewallRuleRequest endpoint and method', function (): void {
        $request = new DeleteFirewallRuleRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/firewall-rules/2')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });
});

describe('Integrations', function (): void {
    it('resolves EnableMaintenanceRequest endpoint and method', function (): void {
        $request = new EnableMaintenanceRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/integrations/laravel-maintenance')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds EnableMaintenanceRequest body with default status', function (): void {
        $request = new EnableMaintenanceRequest(1, 2);
        $body = getDefaultBody($request);

        expect($body)->toBe(['status' => 503]);
    });

    it('builds EnableMaintenanceRequest body with secret and status', function (): void {
        $request = new EnableMaintenanceRequest(1, 2, 'my-secret', 500);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('secret', 'my-secret')
            ->toHaveKey('status', 500);
    });

    it('resolves EnableOctaneRequest endpoint and method', function (): void {
        $request = new EnableOctaneRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/integrations/octane')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds EnableOctaneRequest body with defaults', function (): void {
        $request = new EnableOctaneRequest(1, 2);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('server', 'swoole')
            ->toHaveKey('port', 8000);
    });

    it('builds EnableOctaneRequest body with custom values', function (): void {
        $request = new EnableOctaneRequest(1, 2, 'roadrunner', 9000);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('server', 'roadrunner')
            ->toHaveKey('port', 9000);
    });
});

describe('Jobs', function (): void {
    it('resolves ListJobsRequest endpoint and method', function (): void {
        $request = new ListJobsRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/jobs')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetJobRequest endpoint and method', function (): void {
        $request = new GetJobRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/jobs/2')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateJobRequest endpoint and method', function (): void {
        $data = CreateJobData::from(['command' => 'php artisan schedule:run', 'frequency' => 'minutely']);
        $request = new CreateJobRequest(1, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/jobs')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateJobRequest body filtering nulls', function (): void {
        $data = CreateJobData::from(['command' => 'php artisan schedule:run', 'frequency' => 'minutely']);
        $request = new CreateJobRequest(1, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('command', 'php artisan schedule:run')
            ->toHaveKey('frequency', 'minutely')
            ->toHaveKey('user', 'forge')
            ->not->toHaveKey('minute');
    });

    it('resolves DeleteJobRequest endpoint and method', function (): void {
        $request = new DeleteJobRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/jobs/2')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });

    it('resolves GetJobOutputRequest endpoint and method', function (): void {
        $request = new GetJobOutputRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/jobs/2/output')
            ->and(getHttpMethod($request))->toBe('GET');
    });
});

describe('Monitors', function (): void {
    it('resolves ListMonitorsRequest endpoint and method', function (): void {
        $request = new ListMonitorsRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/monitors')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetMonitorRequest endpoint and method', function (): void {
        $request = new GetMonitorRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/monitors/2')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateMonitorRequest endpoint and method', function (): void {
        $data = CreateMonitorData::from(['type' => 'disk']);
        $request = new CreateMonitorRequest(1, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/monitors')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateMonitorRequest body filtering nulls', function (): void {
        $data = CreateMonitorData::from(['type' => 'disk']);
        $request = new CreateMonitorRequest(1, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('type', 'disk')
            ->not->toHaveKey('operator');
    });

    it('resolves DeleteMonitorRequest endpoint and method', function (): void {
        $request = new DeleteMonitorRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/monitors/2')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });
});

describe('NginxTemplates', function (): void {
    it('resolves ListNginxTemplatesRequest endpoint and method', function (): void {
        $request = new ListNginxTemplatesRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/nginx/templates')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetNginxTemplateRequest endpoint and method', function (): void {
        $request = new GetNginxTemplateRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/nginx/templates/2')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetNginxDefaultTemplateRequest endpoint and method', function (): void {
        $request = new GetNginxDefaultTemplateRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/nginx/templates/default')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateNginxTemplateRequest endpoint and method', function (): void {
        $data = CreateNginxTemplateData::from(['name' => 'custom', 'content' => 'server {}']);
        $request = new CreateNginxTemplateRequest(1, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/nginx/templates')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateNginxTemplateRequest body', function (): void {
        $data = CreateNginxTemplateData::from(['name' => 'custom', 'content' => 'server {}']);
        $request = new CreateNginxTemplateRequest(1, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('name', 'custom')
            ->toHaveKey('content', 'server {}');
    });

    it('resolves UpdateNginxTemplateRequest endpoint and method', function (): void {
        $data = UpdateNginxTemplateData::from(['name' => 'updated']);
        $request = new UpdateNginxTemplateRequest(1, 2, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/nginx/templates/2')
            ->and(getHttpMethod($request))->toBe('PUT');
    });

    it('builds UpdateNginxTemplateRequest body filtering nulls', function (): void {
        $data = UpdateNginxTemplateData::from(['name' => 'updated']);
        $request = new UpdateNginxTemplateRequest(1, 2, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('name', 'updated')
            ->not->toHaveKey('content');
    });

    it('resolves DeleteNginxTemplateRequest endpoint and method', function (): void {
        $request = new DeleteNginxTemplateRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/nginx/templates/2')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });
});

describe('Php', function (): void {
    it('resolves InstallPhpRequest endpoint and method', function (): void {
        $request = new InstallPhpRequest(1, 'php83');

        expect($request->resolveEndpoint())->toBe('/servers/1/php/versions')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds InstallPhpRequest body', function (): void {
        $request = new InstallPhpRequest(1, 'php83');
        $body = getDefaultBody($request);

        expect($body)->toBe(['version' => 'php83']);
    });

    it('resolves UpdatePhpRequest endpoint and method', function (): void {
        $request = new UpdatePhpRequest(1, 'php83');

        expect($request->resolveEndpoint())->toBe('/servers/1/php/versions')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds UpdatePhpRequest body', function (): void {
        $request = new UpdatePhpRequest(1, 'php83');
        $body = getDefaultBody($request);

        expect($body)->toBe(['version' => 'php83']);
    });
});

describe('RedirectRules', function (): void {
    it('resolves ListRedirectRulesRequest endpoint and method', function (): void {
        $request = new ListRedirectRulesRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/redirect-rules')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetRedirectRuleRequest endpoint and method', function (): void {
        $request = new GetRedirectRuleRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/redirect-rules/3')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateRedirectRuleRequest endpoint and method', function (): void {
        $data = CreateRedirectRuleData::from(['from' => '/old', 'to' => '/new']);
        $request = new CreateRedirectRuleRequest(1, 2, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/redirect-rules')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateRedirectRuleRequest body filtering nulls', function (): void {
        $data = CreateRedirectRuleData::from(['from' => '/old', 'to' => '/new']);
        $request = new CreateRedirectRuleRequest(1, 2, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('from', '/old')
            ->toHaveKey('to', '/new')
            ->not->toHaveKey('type');
    });

    it('resolves DeleteRedirectRuleRequest endpoint and method', function (): void {
        $request = new DeleteRedirectRuleRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/redirect-rules/3')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });
});

describe('SecurityRules', function (): void {
    it('resolves ListSecurityRulesRequest endpoint and method', function (): void {
        $request = new ListSecurityRulesRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/security-rules')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetSecurityRuleRequest endpoint and method', function (): void {
        $request = new GetSecurityRuleRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/security-rules/3')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateSecurityRuleRequest endpoint and method', function (): void {
        $data = CreateSecurityRuleData::from(['name' => 'Admin', 'path' => '/admin']);
        $request = new CreateSecurityRuleRequest(1, 2, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/security-rules')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateSecurityRuleRequest body filtering nulls', function (): void {
        $data = CreateSecurityRuleData::from(['name' => 'Admin', 'path' => '/admin']);
        $request = new CreateSecurityRuleRequest(1, 2, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('name', 'Admin')
            ->toHaveKey('path', '/admin')
            ->not->toHaveKey('credentials');
    });

    it('resolves DeleteSecurityRuleRequest endpoint and method', function (): void {
        $request = new DeleteSecurityRuleRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/security-rules/3')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });
});

describe('Servers', function (): void {
    it('resolves ListServersRequest endpoint and method', function (): void {
        $request = new ListServersRequest();

        expect($request->resolveEndpoint())->toBe('/servers')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetServerRequest endpoint and method', function (): void {
        $request = new GetServerRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateServerRequest endpoint and method', function (): void {
        $data = CreateServerData::from([
            'name' => 'production',
            'provider' => 'ocean2',
            'type' => 'app',
            'ubuntu_version' => '24.04',
        ]);
        $request = new CreateServerRequest($data);

        expect($request->resolveEndpoint())->toBe('/servers')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateServerRequest body', function (): void {
        $data = CreateServerData::from([
            'name' => 'production',
            'provider' => 'ocean2',
            'type' => 'app',
            'ubuntu_version' => '24.04',
            'ocean2' => ['region_id' => 'nyc1', 'size_id' => 's-1vcpu-1gb'],
        ]);
        $request = new CreateServerRequest($data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('name', 'production')
            ->toHaveKey('provider', 'ocean2')
            ->toHaveKey('type', 'app')
            ->toHaveKey('ubuntuVersion', '24.04')
            ->toHaveKey('ocean2');
    });

    it('resolves UpdateServerRequest endpoint and method', function (): void {
        $data = UpdateServerData::from(['name' => 'staging']);
        $request = new UpdateServerRequest(1, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1')
            ->and(getHttpMethod($request))->toBe('PUT');
    });

    it('builds UpdateServerRequest body', function (): void {
        $data = UpdateServerData::from(['name' => 'staging']);
        $request = new UpdateServerRequest(1, $data);
        $body = getDefaultBody($request);

        expect($body)->toHaveKey('name', 'staging');
    });

    it('resolves DeleteServerRequest endpoint and method', function (): void {
        $request = new DeleteServerRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });

    it('resolves RebootServerRequest endpoint and method', function (): void {
        $request = new RebootServerRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/actions')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds RebootServerRequest body', function (): void {
        $request = new RebootServerRequest(1);
        $body = getDefaultBody($request);

        expect($body)->toBe(['action' => 'reboot']);
    });

    it('resolves ListEventsRequest endpoint and method', function (): void {
        $request = new ListEventsRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/events')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetEventOutputRequest endpoint and method', function (): void {
        $request = new GetEventOutputRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/events/2/output')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetServerLogRequest endpoint and method', function (): void {
        $request = new GetServerLogRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/logs')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('builds GetServerLogRequest default query', function (): void {
        $request = new GetServerLogRequest(1);
        $reflection = new ReflectionClass($request);
        $method = $reflection->getMethod('defaultQuery');
        $method->setAccessible(true);
        $query = $method->invoke($request);

        expect($query)->toBe(['file' => 'auth']);
    });

    it('builds GetServerLogRequest custom query', function (): void {
        $request = new GetServerLogRequest(1, 'syslog');
        $reflection = new ReflectionClass($request);
        $method = $reflection->getMethod('defaultQuery');
        $method->setAccessible(true);
        $query = $method->invoke($request);

        expect($query)->toBe(['file' => 'syslog']);
    });

    it('resolves ReactivateServerRequest endpoint and method', function (): void {
        $request = new ReactivateServerRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/actions')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds ReactivateServerRequest body', function (): void {
        $request = new ReactivateServerRequest(1);
        $body = getDefaultBody($request);

        expect($body)->toBe(['action' => 'reactivate']);
    });

    it('resolves ReconnectServerRequest endpoint and method', function (): void {
        $request = new ReconnectServerRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/actions')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds ReconnectServerRequest body', function (): void {
        $request = new ReconnectServerRequest(1);
        $body = getDefaultBody($request);

        expect($body)->toBe(['action' => 'reconnect']);
    });

    it('resolves RevokeServerAccessRequest endpoint and method', function (): void {
        $request = new RevokeServerAccessRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/actions')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds RevokeServerAccessRequest body', function (): void {
        $request = new RevokeServerAccessRequest(1);
        $body = getDefaultBody($request);

        expect($body)->toBe(['action' => 'revoke']);
    });

    it('resolves UpdateDatabasePasswordRequest endpoint and method', function (): void {
        $request = new UpdateDatabasePasswordRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/database/password')
            ->and(getHttpMethod($request))->toBe('PUT');
    });

    it('builds UpdateDatabasePasswordRequest empty body', function (): void {
        $request = new UpdateDatabasePasswordRequest(1);
        $body = getDefaultBody($request);

        expect($body)->toBeEmpty();
    });
});

describe('Services', function (): void {
    it('resolves InstallBlackfireRequest endpoint and method', function (): void {
        $request = new InstallBlackfireRequest(1, 'server-id-token', 'server-token');

        expect($request->resolveEndpoint())->toBe('/servers/1/blackfire/install')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds InstallBlackfireRequest body', function (): void {
        $request = new InstallBlackfireRequest(1, 'server-id-token', 'server-token');
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('server_id', 'server-id-token')
            ->toHaveKey('server_token', 'server-token');
    });

    it('resolves InstallPapertrailRequest endpoint and method', function (): void {
        $request = new InstallPapertrailRequest(1, 'logs.example.com');

        expect($request->resolveEndpoint())->toBe('/servers/1/papertrail/install')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds InstallPapertrailRequest body', function (): void {
        $request = new InstallPapertrailRequest(1, 'logs.example.com');
        $body = getDefaultBody($request);

        expect($body)->toBe(['host' => 'logs.example.com']);
    });

    it('resolves ManageServiceRequest endpoint and method', function (): void {
        $request = new ManageServiceRequest(1, 'nginx', 'restart');

        expect($request->resolveEndpoint())->toBe('/servers/1/services/nginx/actions')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds ManageServiceRequest body', function (): void {
        $request = new ManageServiceRequest(1, 'nginx', 'restart');
        $body = getDefaultBody($request);

        expect($body)->toBe(['action' => 'restart']);
    });
});

describe('SSHKeys', function (): void {
    it('resolves ListSSHKeysRequest endpoint and method', function (): void {
        $request = new ListSSHKeysRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/keys')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetSSHKeyRequest endpoint and method', function (): void {
        $request = new GetSSHKeyRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/keys/2')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateSSHKeyRequest endpoint and method', function (): void {
        $data = CreateSSHKeyData::from(['name' => 'my-key', 'key' => 'ssh-rsa AAAA...']);
        $request = new CreateSSHKeyRequest(1, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/keys')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateSSHKeyRequest body', function (): void {
        $data = CreateSSHKeyData::from(['name' => 'my-key', 'key' => 'ssh-rsa AAAA...']);
        $request = new CreateSSHKeyRequest(1, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('name', 'my-key')
            ->toHaveKey('key', 'ssh-rsa AAAA...');
    });

    it('resolves DeleteSSHKeyRequest endpoint and method', function (): void {
        $request = new DeleteSSHKeyRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/keys/2')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });
});

describe('Sites', function (): void {
    it('resolves ListSitesRequest endpoint and method', function (): void {
        $request = new ListSitesRequest(1);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetSiteRequest endpoint and method', function (): void {
        $request = new GetSiteRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateSiteRequest endpoint and method', function (): void {
        $data = CreateSiteData::from(['domain' => 'example.com', 'projectType' => 'php']);
        $request = new CreateSiteRequest(1, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateSiteRequest body', function (): void {
        $data = CreateSiteData::from(['domain' => 'example.com', 'projectType' => 'php']);
        $request = new CreateSiteRequest(1, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('domain', 'example.com')
            ->toHaveKey('projectType', 'php');
    });

    it('resolves UpdateSiteRequest endpoint and method', function (): void {
        $data = UpdateSiteData::from(['directory' => '/public']);
        $request = new UpdateSiteRequest(1, 2, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2')
            ->and(getHttpMethod($request))->toBe('PUT');
    });

    it('builds UpdateSiteRequest body', function (): void {
        $data = UpdateSiteData::from(['directory' => '/public']);
        $request = new UpdateSiteRequest(1, 2, $data);
        $body = getDefaultBody($request);

        expect($body)->toHaveKey('directory', '/public');
    });

    it('resolves DeleteSiteRequest endpoint and method', function (): void {
        $request = new DeleteSiteRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });

    it('resolves GetSiteLogRequest endpoint and method', function (): void {
        $request = new GetSiteLogRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/logs')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves ClearSiteLogRequest endpoint and method', function (): void {
        $request = new ClearSiteLogRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/logs')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });

    it('resolves ChangePhpVersionRequest endpoint and method', function (): void {
        $request = new ChangePhpVersionRequest(1, 2, 'php83');

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/php')
            ->and(getHttpMethod($request))->toBe('PUT');
    });

    it('builds ChangePhpVersionRequest body', function (): void {
        $request = new ChangePhpVersionRequest(1, 2, 'php83');
        $body = getDefaultBody($request);

        expect($body)->toBe(['version' => 'php83']);
    });

    it('resolves DeploySiteRequest endpoint and method', function (): void {
        $request = new DeploySiteRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/deployments')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('resolves GetDeploymentLogRequest endpoint and method', function (): void {
        $request = new GetDeploymentLogRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/deployment/log')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetDeploymentScriptRequest endpoint and method', function (): void {
        $request = new GetDeploymentScriptRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/deployment/script')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves UpdateDeploymentScriptRequest endpoint and method', function (): void {
        $request = new UpdateDeploymentScriptRequest(1, 2, 'cd /home/forge && git pull');

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/deployment/script')
            ->and(getHttpMethod($request))->toBe('PUT');
    });

    it('builds UpdateDeploymentScriptRequest body', function (): void {
        $request = new UpdateDeploymentScriptRequest(1, 2, 'cd /home/forge && git pull');
        $body = getDefaultBody($request);

        expect($body)->toBe(['content' => 'cd /home/forge && git pull']);
    });

    it('resolves EnableQuickDeployRequest endpoint and method', function (): void {
        $request = new EnableQuickDeployRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/deployment')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('resolves DisableQuickDeployRequest endpoint and method', function (): void {
        $request = new DisableQuickDeployRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/deployment')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });

    it('resolves GetDeploymentHistoryRequest endpoint and method', function (): void {
        $request = new GetDeploymentHistoryRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/deployment-history')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetDeploymentHistoryDeploymentRequest endpoint and method', function (): void {
        $request = new GetDeploymentHistoryDeploymentRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/deployment-history/3')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetDeploymentHistoryOutputRequest endpoint and method', function (): void {
        $request = new GetDeploymentHistoryOutputRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/deployment-history/3/output')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves ResetDeploymentStateRequest endpoint and method', function (): void {
        $request = new ResetDeploymentStateRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/deployment/reset')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('resolves InstallGitRepositoryRequest endpoint and method', function (): void {
        $data = InstallGitRepositoryData::from(['provider' => 'github', 'repository' => 'user/repo']);
        $request = new InstallGitRepositoryRequest(1, 2, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/git')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds InstallGitRepositoryRequest body', function (): void {
        $data = InstallGitRepositoryData::from([
            'provider' => 'github',
            'repository' => 'user/repo',
            'branch' => 'main',
            'composer' => true,
        ]);
        $request = new InstallGitRepositoryRequest(1, 2, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('provider', 'github')
            ->toHaveKey('repository', 'user/repo')
            ->toHaveKey('branch', 'main')
            ->toHaveKey('composer', true);
    });

    it('resolves UpdateGitRepositoryRequest endpoint and method', function (): void {
        $data = UpdateGitRepositoryData::from(['branch' => 'develop']);
        $request = new UpdateGitRepositoryRequest(1, 2, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/git')
            ->and(getHttpMethod($request))->toBe('PUT');
    });

    it('builds UpdateGitRepositoryRequest body', function (): void {
        $data = UpdateGitRepositoryData::from(['branch' => 'develop']);
        $request = new UpdateGitRepositoryRequest(1, 2, $data);
        $body = getDefaultBody($request);

        expect($body)->toHaveKey('branch', 'develop');
    });

    it('resolves DestroyGitRepositoryRequest endpoint and method', function (): void {
        $request = new DestroyGitRepositoryRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/git')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });

    it('resolves CreateDeployKeyRequest endpoint and method', function (): void {
        $request = new CreateDeployKeyRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/deploy-key')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('resolves DeleteDeployKeyRequest endpoint and method', function (): void {
        $request = new DeleteDeployKeyRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/deploy-key')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });

    it('resolves GetEnvFileRequest endpoint and method', function (): void {
        $request = new GetEnvFileRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/environment')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves UpdateEnvFileRequest endpoint and method', function (): void {
        $request = new UpdateEnvFileRequest(1, 2, 'APP_NAME=Laravel');

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/environment')
            ->and(getHttpMethod($request))->toBe('PUT');
    });

    it('builds UpdateEnvFileRequest body', function (): void {
        $request = new UpdateEnvFileRequest(1, 2, 'APP_NAME=Laravel');
        $body = getDefaultBody($request);

        expect($body)->toBe(['content' => 'APP_NAME=Laravel']);
    });

    it('resolves GetNginxConfigRequest endpoint and method', function (): void {
        $request = new GetNginxConfigRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/nginx')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves UpdateNginxConfigRequest endpoint and method', function (): void {
        $request = new UpdateNginxConfigRequest(1, 2, 'server {}');

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/nginx')
            ->and(getHttpMethod($request))->toBe('PUT');
    });

    it('builds UpdateNginxConfigRequest body', function (): void {
        $request = new UpdateNginxConfigRequest(1, 2, 'server {}');
        $body = getDefaultBody($request);

        expect($body)->toBe(['content' => 'server {}']);
    });

    it('resolves ExecuteSiteCommandRequest endpoint and method', function (): void {
        $data = ExecuteSiteCommandData::from(['command' => 'php artisan migrate']);
        $request = new ExecuteSiteCommandRequest(1, 2, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/commands')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds ExecuteSiteCommandRequest body', function (): void {
        $data = ExecuteSiteCommandData::from(['command' => 'php artisan migrate']);
        $request = new ExecuteSiteCommandRequest(1, 2, $data);
        $body = getDefaultBody($request);

        expect($body)->toHaveKey('command', 'php artisan migrate');
    });

    it('resolves GetSiteCommandRequest endpoint and method', function (): void {
        $request = new GetSiteCommandRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/commands/3')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves ListCommandHistoryRequest endpoint and method', function (): void {
        $request = new ListCommandHistoryRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/commands')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves InstallWordPressRequest endpoint and method', function (): void {
        $request = new InstallWordPressRequest(1, 2, 'wp_db', 'wp_user');

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/wordpress')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds InstallWordPressRequest body without password', function (): void {
        $request = new InstallWordPressRequest(1, 2, 'wp_db', 'wp_user');
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('database', 'wp_db')
            ->toHaveKey('user', 'wp_user')
            ->not->toHaveKey('password');
    });

    it('builds InstallWordPressRequest body with password', function (): void {
        $request = new InstallWordPressRequest(1, 2, 'wp_db', 'wp_user', 'secret');
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('database', 'wp_db')
            ->toHaveKey('user', 'wp_user')
            ->toHaveKey('password', 'secret');
    });

    it('resolves UninstallWordPressRequest endpoint and method', function (): void {
        $request = new UninstallWordPressRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/wordpress')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });

    it('resolves InstallPhpMyAdminRequest endpoint and method', function (): void {
        $request = new InstallPhpMyAdminRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/phpmyadmin')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('resolves UninstallPhpMyAdminRequest endpoint and method', function (): void {
        $request = new UninstallPhpMyAdminRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/phpmyadmin')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });

    it('resolves GetPackagesAuthRequest endpoint and method', function (): void {
        $request = new GetPackagesAuthRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/packages')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves UpdatePackagesAuthRequest endpoint and method', function (): void {
        $request = new UpdatePackagesAuthRequest(1, 2, [['type' => 'composer', 'url' => 'https://repo.example.com']]);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/packages')
            ->and(getHttpMethod($request))->toBe('PUT');
    });

    it('builds UpdatePackagesAuthRequest body', function (): void {
        $packages = [['type' => 'composer', 'url' => 'https://repo.example.com']];
        $request = new UpdatePackagesAuthRequest(1, 2, $packages);
        $body = getDefaultBody($request);

        expect($body)->toBe(['packages' => $packages]);
    });

    it('resolves SetDeploymentFailureEmailsRequest endpoint and method', function (): void {
        $request = new SetDeploymentFailureEmailsRequest(1, 2, ['admin@example.com']);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/deployment-failure-emails')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds SetDeploymentFailureEmailsRequest body', function (): void {
        $emails = ['admin@example.com', 'dev@example.com'];
        $request = new SetDeploymentFailureEmailsRequest(1, 2, $emails);
        $body = getDefaultBody($request);

        expect($body)->toBe(['emails' => $emails]);
    });

    it('resolves ListAliasesRequest endpoint and method', function (): void {
        $request = new ListAliasesRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/aliases')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves UpdateAliasesRequest endpoint and method', function (): void {
        $request = new UpdateAliasesRequest(1, 2, ['www.example.com']);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/aliases')
            ->and(getHttpMethod($request))->toBe('PUT');
    });

    it('builds UpdateAliasesRequest body', function (): void {
        $aliases = ['www.example.com', 'app.example.com'];
        $request = new UpdateAliasesRequest(1, 2, $aliases);
        $body = getDefaultBody($request);

        expect($body)->toBe(['aliases' => $aliases]);
    });

    it('resolves GetLoadBalancingRequest endpoint and method', function (): void {
        $request = new GetLoadBalancingRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/balancing')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves UpdateLoadBalancingRequest endpoint and method', function (): void {
        $request = new UpdateLoadBalancingRequest(1, 2, [['id' => 10, 'weight' => 5]]);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/balancing')
            ->and(getHttpMethod($request))->toBe('PUT');
    });

    it('builds UpdateLoadBalancingRequest body with defaults', function (): void {
        $servers = [['id' => 10, 'weight' => 5]];
        $request = new UpdateLoadBalancingRequest(1, 2, $servers);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('servers', $servers)
            ->toHaveKey('method', 'round_robin');
    });

    it('builds UpdateLoadBalancingRequest body with custom method', function (): void {
        $servers = [['id' => 10, 'weight' => 5]];
        $request = new UpdateLoadBalancingRequest(1, 2, $servers, 'least_conn');
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('servers', $servers)
            ->toHaveKey('method', 'least_conn');
    });
});

describe('User', function (): void {
    it('resolves GetUserRequest endpoint and method', function (): void {
        $request = new GetUserRequest();

        expect($request->resolveEndpoint())->toBe('https://forge.laravel.com/api/user')
            ->and(getHttpMethod($request))->toBe('GET');
    });
});

describe('Webhooks', function (): void {
    it('resolves ListWebhooksRequest endpoint and method', function (): void {
        $request = new ListWebhooksRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/webhooks')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetWebhookRequest endpoint and method', function (): void {
        $request = new GetWebhookRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/webhooks/3')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateWebhookRequest endpoint and method', function (): void {
        $data = CreateWebhookData::from(['url' => 'https://example.com/webhook']);
        $request = new CreateWebhookRequest(1, 2, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/webhooks')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateWebhookRequest body', function (): void {
        $data = CreateWebhookData::from(['url' => 'https://example.com/webhook']);
        $request = new CreateWebhookRequest(1, 2, $data);
        $body = getDefaultBody($request);

        expect($body)->toHaveKey('url', 'https://example.com/webhook');
    });

    it('resolves DeleteWebhookRequest endpoint and method', function (): void {
        $request = new DeleteWebhookRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/webhooks/3')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });
});

describe('Workers', function (): void {
    it('resolves ListWorkersRequest endpoint and method', function (): void {
        $request = new ListWorkersRequest(1, 2);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/workers')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves GetWorkerRequest endpoint and method', function (): void {
        $request = new GetWorkerRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/workers/3')
            ->and(getHttpMethod($request))->toBe('GET');
    });

    it('resolves CreateWorkerRequest endpoint and method', function (): void {
        $data = CreateWorkerData::from(['connection' => 'redis', 'queue' => 'default']);
        $request = new CreateWorkerRequest(1, 2, $data);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/workers')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('builds CreateWorkerRequest body filtering nulls', function (): void {
        $data = CreateWorkerData::from(['connection' => 'redis', 'queue' => 'default']);
        $request = new CreateWorkerRequest(1, 2, $data);
        $body = getDefaultBody($request);

        expect($body)
            ->toHaveKey('connection', 'redis')
            ->toHaveKey('queue', 'default')
            ->not->toHaveKey('timeout');
    });

    it('resolves RestartWorkerRequest endpoint and method', function (): void {
        $request = new RestartWorkerRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/workers/3/restart')
            ->and(getHttpMethod($request))->toBe('POST');
    });

    it('resolves DeleteWorkerRequest endpoint and method', function (): void {
        $request = new DeleteWorkerRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/workers/3')
            ->and(getHttpMethod($request))->toBe('DELETE');
    });

    it('resolves GetWorkerOutputRequest endpoint and method', function (): void {
        $request = new GetWorkerOutputRequest(1, 2, 3);

        expect($request->resolveEndpoint())->toBe('/servers/1/sites/2/workers/3/output')
            ->and(getHttpMethod($request))->toBe('GET');
    });
});

describe('Cursor Pagination', function (): void {
    it('includes cursor in query when provided', function (object $request): void {
        $query = getDefaultQuery($request);

        expect($query)
            ->toHaveKey('page[size]', 10)
            ->toHaveKey('page[cursor]', 'abc123');
    })->with([
        'ListServersRequest' => [fn (): ListServersRequest => new ListServersRequest('abc123', 10)],
        'ListSitesRequest' => [fn (): ListSitesRequest => new ListSitesRequest(1, 'abc123', 10)],
        'ListDatabasesRequest' => [fn (): ListDatabasesRequest => new ListDatabasesRequest(1, 'abc123', 10)],
        'ListDatabaseUsersRequest' => [fn (): ListDatabaseUsersRequest => new ListDatabaseUsersRequest(1, 'abc123', 10)],
        'ListBackupConfigurationsRequest' => [fn (): ListBackupConfigurationsRequest => new ListBackupConfigurationsRequest(1, 'abc123', 10)],
        'ListCertificatesRequest' => [fn (): ListCertificatesRequest => new ListCertificatesRequest(1, 1, 'abc123', 10)],
        'ListDaemonsRequest' => [fn (): ListDaemonsRequest => new ListDaemonsRequest(1, 'abc123', 10)],
        'ListFirewallRulesRequest' => [fn (): ListFirewallRulesRequest => new ListFirewallRulesRequest(1, 'abc123', 10)],
        'ListJobsRequest' => [fn (): ListJobsRequest => new ListJobsRequest(1, 'abc123', 10)],
        'ListMonitorsRequest' => [fn (): ListMonitorsRequest => new ListMonitorsRequest(1, 'abc123', 10)],
        'ListWorkersRequest' => [fn (): ListWorkersRequest => new ListWorkersRequest(1, 1, 'abc123', 10)],
        'ListWebhooksRequest' => [fn (): ListWebhooksRequest => new ListWebhooksRequest(1, 1, 'abc123', 10)],
        'ListSSHKeysRequest' => [fn (): ListSSHKeysRequest => new ListSSHKeysRequest(1, 'abc123', 10)],
        'ListSecurityRulesRequest' => [fn (): ListSecurityRulesRequest => new ListSecurityRulesRequest(1, 1, 'abc123', 10)],
        'ListRedirectRulesRequest' => [fn (): ListRedirectRulesRequest => new ListRedirectRulesRequest(1, 1, 'abc123', 10)],
        'ListNginxTemplatesRequest' => [fn (): ListNginxTemplatesRequest => new ListNginxTemplatesRequest(1, 'abc123', 10)],
        'ListEventsRequest' => [fn (): ListEventsRequest => new ListEventsRequest(1, 'abc123', 10)],
        'ListCommandHistoryRequest' => [fn (): ListCommandHistoryRequest => new ListCommandHistoryRequest(1, 1, 'abc123', 10)],
        'ListAliasesRequest' => [fn (): ListAliasesRequest => new ListAliasesRequest(1, 1, 'abc123', 10)],
    ]);

    it('omits cursor from query when null', function (object $request): void {
        $query = getDefaultQuery($request);

        expect($query)
            ->toHaveKey('page[size]', 30)
            ->not->toHaveKey('page[cursor]');
    })->with([
        'ListServersRequest' => [fn (): ListServersRequest => new ListServersRequest()],
        'ListSitesRequest' => [fn (): ListSitesRequest => new ListSitesRequest(1)],
        'ListDatabasesRequest' => [fn (): ListDatabasesRequest => new ListDatabasesRequest(1)],
    ]);
});
