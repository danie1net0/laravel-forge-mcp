<?php

declare(strict_types=1);

use App\Integrations\Forge\{ForgeClient, ForgeConnector};
use App\Integrations\Forge\Resources\{BackupResource, CertificateResource, CredentialResource, DaemonResource, DatabaseResource, DatabaseUserResource, FirewallResource, IntegrationResource, JobResource, MonitorResource, NginxTemplateResource, PhpResource, RecipeResource, RedirectRuleResource, RegionResource, SSHKeyResource, SecurityRuleResource, ServerResource, ServiceResource, SiteResource, UserResource, WebhookResource, WorkerResource};
use Saloon\Http\Auth\TokenAuthenticator;

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
});

describe('ForgeConnector', function (): void {
    it('resolves correct base URL', function (): void {
        $connector = new ForgeConnector('test-token');

        expect($connector->resolveBaseUrl())->toBe('https://forge.laravel.com/api/v1');
    });

    it('has correct default headers', function (): void {
        $connector = new ForgeConnector('test-token');

        $headers = $connector->headers()->all();

        expect($headers)
            ->toHaveKey('Accept', 'application/json')
            ->toHaveKey('Content-Type', 'application/json');
    });

    it('uses token authenticator with provided token', function (): void {
        $connector = new ForgeConnector('my-secret-token');

        $authenticator = $connector->getAuthenticator();

        expect($authenticator)->toBeInstanceOf(TokenAuthenticator::class);
    });
});

describe('ForgeClient', function (): void {
    it('throws RuntimeException when no API token configured', function (): void {
        config(['services.forge.api_token' => null]);

        new ForgeClient();
    })->throws(RuntimeException::class, 'Forge API token not configured');

    it('accepts API token via constructor', function (): void {
        config(['services.forge.api_token' => null]);

        $client = new ForgeClient('constructor-token');

        expect($client)->toBeInstanceOf(ForgeClient::class);
    });

    it('accepts API token via config', function (): void {
        config(['services.forge.api_token' => 'config-token']);

        $client = new ForgeClient();

        expect($client)->toBeInstanceOf(ForgeClient::class);
    });

    it('returns the correct resource instance', function (string $method, string $expectedClass): void {
        $client = new ForgeClient('test-token');

        expect($client->{$method}())->toBeInstanceOf($expectedClass);
    })->with([
        'servers' => ['servers', ServerResource::class],
        'sites' => ['sites', SiteResource::class],
        'databases' => ['databases', DatabaseResource::class],
        'databaseUsers' => ['databaseUsers', DatabaseUserResource::class],
        'certificates' => ['certificates', CertificateResource::class],
        'jobs' => ['jobs', JobResource::class],
        'daemons' => ['daemons', DaemonResource::class],
        'firewall' => ['firewall', FirewallResource::class],
        'workers' => ['workers', WorkerResource::class],
        'webhooks' => ['webhooks', WebhookResource::class],
        'securityRules' => ['securityRules', SecurityRuleResource::class],
        'sshKeys' => ['sshKeys', SSHKeyResource::class],
        'redirectRules' => ['redirectRules', RedirectRuleResource::class],
        'recipes' => ['recipes', RecipeResource::class],
        'backups' => ['backups', BackupResource::class],
        'credentials' => ['credentials', CredentialResource::class],
        'monitors' => ['monitors', MonitorResource::class],
        'nginxTemplates' => ['nginxTemplates', NginxTemplateResource::class],
        'user' => ['user', UserResource::class],
        'services' => ['services', ServiceResource::class],
        'php' => ['php', PhpResource::class],
        'regions' => ['regions', RegionResource::class],
        'integrations' => ['integrations', IntegrationResource::class],
    ]);
});
