<?php

declare(strict_types=1);

namespace App\Integrations\Forge;

use App\Integrations\Forge\Resources\{BackupResource, CertificateResource, DaemonResource, DatabaseResource, DatabaseUserResource, FirewallResource, IntegrationResource, JobResource, MonitorResource, NginxTemplateResource, PhpResource, RedirectRuleResource, SSHKeyResource, SecurityRuleResource, ServerResource, ServiceResource, SiteResource, UserResource, WebhookResource, WorkerResource};
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ForgeClient
{
    protected ForgeConnector $connector;

    public function __construct(?string $apiToken = null, ?string $organization = null)
    {
        /** @var string|null $token */
        $token = $apiToken ?? config('services.forge.api_token');

        if (! $token) {
            throw new RuntimeException('Forge API token not configured');
        }

        /** @var string|null $organizationSlug */
        $organizationSlug = $organization ?? config('services.forge.organization');

        if (! $organizationSlug) {
            $organizationSlug = $this->discoverOrganizationSlug($token);
        }

        $this->connector = new ForgeConnector($token, $organizationSlug);
    }

    public function servers(): ServerResource
    {
        return new ServerResource($this->connector);
    }

    public function sites(): SiteResource
    {
        return new SiteResource($this->connector);
    }

    public function databases(): DatabaseResource
    {
        return new DatabaseResource($this->connector);
    }

    public function databaseUsers(): DatabaseUserResource
    {
        return new DatabaseUserResource($this->connector);
    }

    public function certificates(): CertificateResource
    {
        return new CertificateResource($this->connector);
    }

    public function jobs(): JobResource
    {
        return new JobResource($this->connector);
    }

    public function daemons(): DaemonResource
    {
        return new DaemonResource($this->connector);
    }

    public function firewall(): FirewallResource
    {
        return new FirewallResource($this->connector);
    }

    public function workers(): WorkerResource
    {
        return new WorkerResource($this->connector);
    }

    public function webhooks(): WebhookResource
    {
        return new WebhookResource($this->connector);
    }

    public function securityRules(): SecurityRuleResource
    {
        return new SecurityRuleResource($this->connector);
    }

    public function sshKeys(): SSHKeyResource
    {
        return new SSHKeyResource($this->connector);
    }

    public function redirectRules(): RedirectRuleResource
    {
        return new RedirectRuleResource($this->connector);
    }

    public function backups(): BackupResource
    {
        return new BackupResource($this->connector);
    }

    public function monitors(): MonitorResource
    {
        return new MonitorResource($this->connector);
    }

    public function nginxTemplates(): NginxTemplateResource
    {
        return new NginxTemplateResource($this->connector);
    }

    public function user(): UserResource
    {
        return new UserResource($this->connector);
    }

    public function services(): ServiceResource
    {
        return new ServiceResource($this->connector);
    }

    public function php(): PhpResource
    {
        return new PhpResource($this->connector);
    }

    public function integrations(): IntegrationResource
    {
        return new IntegrationResource($this->connector);
    }

    private function discoverOrganizationSlug(string $apiToken): string
    {
        try {
            $response = Http::withToken($apiToken)
                ->acceptJson()
                ->timeout(10)
                ->get('https://forge.laravel.com/api/user');
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Could not connect to Forge API to discover organization. Set FORGE_ORGANIZATION in your environment.',
                previous: $exception,
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Invalid Forge API token or API error. Please verify your FORGE_API_TOKEN.',
            );
        }

        $body = $response->json();
        $organizations = $body['organizations']
            ?? $body['data']['attributes']['organizations']
            ?? $body['data']['organizations']
            ?? null;

        if (! is_array($organizations) || $organizations === []) {
            $rawKeys = is_array($body) ? implode(', ', array_keys($body)) : 'non-array response';

            throw new RuntimeException(
                "Could not auto-discover Forge organization (API response keys: {$rawKeys}). "
                . 'Please set FORGE_ORGANIZATION in your environment (e.g., FORGE_ORGANIZATION=my-org-slug). '
                . 'Find your org slug in the Forge dashboard URL: forge.laravel.com/app/orgs/{your-slug}',
            );
        }

        if (count($organizations) === 1) {
            return (string) ($organizations[0]['slug'] ?? $organizations[0]['id'] ?? '');
        }

        $slugs = implode(', ', array_map(
            fn (array $org): string => (string) ($org['slug'] ?? $org['id'] ?? 'unknown'),
            $organizations,
        ));

        throw new RuntimeException(
            "Multiple Forge organizations found: {$slugs}. Please set FORGE_ORGANIZATION in your environment to specify which one to use.",
        );
    }
}
