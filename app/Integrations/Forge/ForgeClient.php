<?php

declare(strict_types=1);

namespace App\Integrations\Forge;

use App\Integrations\Forge\Resources\{BackupResource, CertificateResource, CredentialResource, DaemonResource, DatabaseResource, DatabaseUserResource, FirewallResource, IntegrationResource, JobResource, MonitorResource, NginxTemplateResource, PhpResource, RecipeResource, RedirectRuleResource, RegionResource, SecurityRuleResource, ServerResource, ServiceResource, SiteResource, SSHKeyResource, UserResource, WebhookResource, WorkerResource};
use RuntimeException;

class ForgeClient
{
    protected ForgeConnector $connector;

    public function __construct(?string $apiToken = null)
    {
        /** @var string|null $token */
        $token = $apiToken ?? config('services.forge.api_token');

        if (! $token) {
            throw new RuntimeException('Forge API token not configured');
        }

        $this->connector = new ForgeConnector($token);
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

    public function recipes(): RecipeResource
    {
        return new RecipeResource($this->connector);
    }

    public function backups(): BackupResource
    {
        return new BackupResource($this->connector);
    }

    public function credentials(): CredentialResource
    {
        return new CredentialResource($this->connector);
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

    public function regions(): RegionResource
    {
        return new RegionResource($this->connector);
    }

    public function integrations(): IntegrationResource
    {
        return new IntegrationResource($this->connector);
    }
}
