<?php

declare(strict_types=1);

namespace App\Services;

use Laravel\Forge\Forge;
use Laravel\Forge\Resources\{Certificate, Daemon, Database, DatabaseUser, FirewallRule, Job, Server, Site};
use RuntimeException;

class ForgeService
{
    private Forge $forge;

    public function __construct()
    {
        /** @var string|null $token */
        $token = config('services.forge.api_token');

        if (! $token) {
            throw new RuntimeException('Forge API token not configured. Please set FORGE_API_TOKEN in your .env file.');
        }

        $this->forge = new Forge($token);
    }

    /**
     * @return Server[]
     */
    public function listServers(): array
    {
        return $this->forge->servers();
    }

    public function getServer(int $serverId): Server
    {
        return $this->forge->server((string) $serverId);
    }

    public function createServer(array $data): Server
    {
        return $this->forge->createServer($data);
    }

    public function updateServer(int $serverId, array $data): Server
    {
        return $this->forge->updateServer((string) $serverId, $data);
    }

    public function deleteServer(int $serverId): void
    {
        $this->forge->deleteServer((string) $serverId);
    }

    public function rebootServer(int $serverId): void
    {
        $this->forge->rebootServer((string) $serverId);
    }

    /**
     * @return Site[]
     */
    public function listSites(int $serverId): array
    {
        return $this->forge->sites($serverId);
    }

    public function getSite(int $serverId, int $siteId): Site
    {
        return $this->forge->site($serverId, $siteId);
    }

    public function createSite(int $serverId, array $data): Site
    {
        return $this->forge->createSite($serverId, $data);
    }

    public function updateSite(int $serverId, int $siteId, array $data): Site
    {
        return $this->forge->updateSite($serverId, $siteId, $data);
    }

    public function deleteSite(int $serverId, int $siteId): void
    {
        $this->forge->deleteSite($serverId, $siteId);
    }

    public function deploySite(int $serverId, int $siteId): void
    {
        $this->forge->deploySite($serverId, $siteId);
    }

    public function getSiteDeploymentScript(int $serverId, int $siteId): string
    {
        return $this->forge->siteDeploymentScript($serverId, $siteId);
    }

    public function updateSiteDeploymentScript(int $serverId, int $siteId, string $content): void
    {
        $this->forge->updateSiteDeploymentScript($serverId, $siteId, $content);
    }

    public function enableQuickDeploy(int $serverId, int $siteId): void
    {
        $this->forge->enableQuickDeploy($serverId, $siteId);
    }

    public function disableQuickDeploy(int $serverId, int $siteId): void
    {
        $this->forge->disableQuickDeploy($serverId, $siteId);
    }

    public function siteDeploymentLog(int $serverId, int $siteId): ?string
    {
        return $this->forge->siteDeploymentLog($serverId, $siteId);
    }

    public function installGitRepositoryOnSite(int $serverId, int $siteId, array $data): void
    {
        $this->forge->installGitRepositoryOnSite($serverId, $siteId, $data);
    }

    /**
     * @return Certificate[]
     */
    public function listCertificates(int $serverId, int $siteId): array
    {
        return $this->forge->certificates($serverId, $siteId);
    }

    public function getCertificate(int $serverId, int $siteId, int $certificateId): Certificate
    {
        return $this->forge->certificate($serverId, $siteId, $certificateId);
    }

    public function obtainLetsEncryptCertificate(int $serverId, int $siteId, array $data): Certificate
    {
        return $this->forge->obtainLetsEncryptCertificate($serverId, $siteId, $data);
    }

    public function installCertificate(int $serverId, int $siteId, int $certificateId, array $data = []): void
    {
        $this->forge->installCertificate($serverId, $siteId, $certificateId, $data);
    }

    public function deleteCertificate(int $serverId, int $siteId, int $certificateId): void
    {
        $this->forge->deleteCertificate($serverId, $siteId, $certificateId);
    }

    /**
     * @return Database[]
     */
    public function listDatabases(int $serverId): array
    {
        return $this->forge->databases($serverId);
    }

    public function getDatabase(int $serverId, int $databaseId): Database
    {
        return $this->forge->database($serverId, $databaseId);
    }

    public function createDatabase(int $serverId, array $data): Database
    {
        return $this->forge->createDatabase($serverId, $data);
    }

    public function updateDatabase(int $serverId, int $databaseId, array $data): Database
    {
        return $this->forge->updateDatabase($serverId, $databaseId, $data);
    }

    public function deleteDatabase(int $serverId, int $databaseId): void
    {
        $this->forge->deleteDatabase($serverId, $databaseId);
    }

    public function listDatabaseUsers(int $serverId): array
    {
        return $this->forge->databaseUsers($serverId);
    }

    public function getDatabaseUser(int $serverId, int $userId): DatabaseUser
    {
        return $this->forge->databaseUser($serverId, $userId);
    }

    public function createDatabaseUser(int $serverId, array $data): DatabaseUser
    {
        return $this->forge->createDatabaseUser($serverId, $data);
    }

    public function updateDatabaseUser(int $serverId, int $userId, array $data): DatabaseUser
    {
        return $this->forge->updateDatabaseUser($serverId, $userId, $data);
    }

    public function deleteDatabaseUser(int $serverId, int $userId): void
    {
        $this->forge->deleteDatabaseUser($serverId, $userId);
    }

    /**
     * @return Job[]
     */
    public function listJobs(int $serverId): array
    {
        return $this->forge->jobs($serverId);
    }

    public function getJob(int $serverId, int $jobId): Job
    {
        return $this->forge->job($serverId, $jobId);
    }

    public function createJob(int $serverId, array $data): Job
    {
        return $this->forge->createJob($serverId, $data);
    }

    public function deleteJob(int $serverId, int $jobId): void
    {
        $this->forge->deleteJob($serverId, $jobId);
    }

    /**
     * @return Daemon[]
     */
    public function listDaemons(int $serverId): array
    {
        return $this->forge->daemons($serverId);
    }

    public function getDaemon(int $serverId, int $daemonId): Daemon
    {
        return $this->forge->daemon($serverId, $daemonId);
    }

    public function createDaemon(int $serverId, array $data): Daemon
    {
        return $this->forge->createDaemon($serverId, $data);
    }

    public function restartDaemon(int $serverId, int $daemonId): void
    {
        $this->forge->restartDaemon($serverId, $daemonId);
    }

    public function deleteDaemon(int $serverId, int $daemonId): void
    {
        $this->forge->deleteDaemon($serverId, $daemonId);
    }

    /**
     * @return FirewallRule[]
     */
    public function listFirewallRules(int $serverId): array
    {
        return $this->forge->firewallRules($serverId);
    }

    public function getFirewallRule(int $serverId, int $ruleId): FirewallRule
    {
        return $this->forge->firewallRule($serverId, $ruleId);
    }

    public function createFirewallRule(int $serverId, array $data): FirewallRule
    {
        return $this->forge->createFirewallRule($serverId, $data);
    }

    public function deleteFirewallRule(int $serverId, int $ruleId): void
    {
        $this->forge->deleteFirewallRule($serverId, $ruleId);
    }

    public function getForgeInstance(): Forge
    {
        return $this->forge;
    }
}
