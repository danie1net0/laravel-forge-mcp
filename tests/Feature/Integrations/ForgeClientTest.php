<?php

declare(strict_types=1);

use App\Integrations\Forge\{ForgeClient, ForgeConnector};
use App\Integrations\Forge\Resources\{BackupResource, CertificateResource, DaemonResource, DatabaseResource, DatabaseUserResource, FirewallResource, IntegrationResource, JobResource, MonitorResource, NginxTemplateResource, PhpResource, RedirectRuleResource, SSHKeyResource, SecurityRuleResource, ServerResource, ServiceResource, SiteResource, UserResource, WebhookResource};
use Illuminate\Support\Facades\Http;
use Saloon\Http\Auth\TokenAuthenticator;

beforeEach(function (): void {
    config(['services.forge.api_token' => 'test-token']);
    config(['services.forge.organization' => 'test-org']);
});

describe('ForgeConnector', function (): void {
    it('resolves correct base URL', function (): void {
        $connector = new ForgeConnector('test-token', 'test-org');

        expect($connector->resolveBaseUrl())->toBe('https://forge.laravel.com/api/orgs/test-org');
    });

    it('has correct default headers', function (): void {
        $connector = new ForgeConnector('test-token', 'test-org');

        $headers = $connector->headers()->all();

        expect($headers)
            ->toHaveKey('Content-Type', 'application/json');
    });

    it('uses token authenticator with provided token', function (): void {
        $connector = new ForgeConnector('my-secret-token', 'test-org');

        $authenticator = $connector->getAuthenticator();

        expect($authenticator)->toBeInstanceOf(TokenAuthenticator::class);
    });
});

describe('ForgeClient', function (): void {
    it('throws RuntimeException when no API token configured', function (): void {
        config(['services.forge.api_token' => null]);

        new ForgeClient();
    })->throws(RuntimeException::class, 'Forge API token not configured');

    it('auto-discovers organization when not configured and single org returned', function (): void {
        config(['services.forge.organization' => null]);

        Http::fake([
            'forge.laravel.com/api/orgs' => Http::response([
                'data' => [
                    ['type' => 'organizations', 'attributes' => ['slug' => 'my-org', 'name' => 'My Org']],
                ],
            ]),
        ]);

        $client = new ForgeClient('test-token');

        expect($client)->toBeInstanceOf(ForgeClient::class);
    });

    it('throws when auto-discovery finds multiple organizations', function (): void {
        config(['services.forge.organization' => null]);

        Http::fake([
            'forge.laravel.com/api/orgs' => Http::response([
                'data' => [
                    ['type' => 'organizations', 'attributes' => ['slug' => 'org-one', 'name' => 'Org One']],
                    ['type' => 'organizations', 'attributes' => ['slug' => 'org-two', 'name' => 'Org Two']],
                ],
            ]),
        ]);

        new ForgeClient('test-token');
    })->throws(RuntimeException::class, 'Multiple Forge organizations found');

    it('throws when auto-discovery finds no organizations', function (): void {
        config(['services.forge.organization' => null]);

        Http::fake([
            'forge.laravel.com/api/orgs' => Http::response(['data' => []]),
        ]);

        new ForgeClient('test-token');
    })->throws(RuntimeException::class, 'No Forge organizations found');

    it('throws when auto-discovery API call fails', function (): void {
        config(['services.forge.organization' => null]);

        Http::fake([
            'forge.laravel.com/api/orgs' => Http::response([], 401),
        ]);

        new ForgeClient('test-token');
    })->throws(RuntimeException::class, 'Invalid Forge API token');

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
        'webhooks' => ['webhooks', WebhookResource::class],
        'securityRules' => ['securityRules', SecurityRuleResource::class],
        'sshKeys' => ['sshKeys', SSHKeyResource::class],
        'redirectRules' => ['redirectRules', RedirectRuleResource::class],
        'backups' => ['backups', BackupResource::class],
        'monitors' => ['monitors', MonitorResource::class],
        'nginxTemplates' => ['nginxTemplates', NginxTemplateResource::class],
        'user' => ['user', UserResource::class],
        'services' => ['services', ServiceResource::class],
        'php' => ['php', PhpResource::class],
        'integrations' => ['integrations', IntegrationResource::class],
    ]);
});
