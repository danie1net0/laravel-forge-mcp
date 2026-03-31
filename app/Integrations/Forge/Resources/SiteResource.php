<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Sites\{CreateSiteData, ExecuteSiteCommandData, InstallGitRepositoryData, SiteCollectionData, SiteData, UpdateGitRepositoryData, UpdateSiteData};
use App\Integrations\Forge\Requests\Sites\{CreateDeployKeyRequest, CreateSiteRequest, DeleteDeployKeyRequest, DeleteSiteRequest, DeploySiteRequest, DestroyGitRepositoryRequest, ExecuteSiteCommandRequest, GetDeploymentHistoryDeploymentRequest, GetDeploymentHistoryRequest, GetDeploymentScriptRequest, GetSiteCommandRequest, GetSiteRequest, InstallGitRepositoryRequest, ListCommandHistoryRequest, ListSitesRequest, UpdateDeploymentScriptRequest, UpdateGitRepositoryRequest, UpdateSiteRequest};

class SiteResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId, ?string $cursor = null, int $pageSize = 30): SiteCollectionData
    {
        $request = new ListSitesRequest($serverId, $cursor, $pageSize);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $siteId): SiteData
    {
        $request = new GetSiteRequest($serverId, $siteId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(int $serverId, CreateSiteData $data): SiteData
    {
        $request = new CreateSiteRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function update(int $serverId, int $siteId, UpdateSiteData $data): SiteData
    {
        $request = new UpdateSiteRequest($serverId, $siteId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, int $siteId): void
    {
        $this->connector->send(new DeleteSiteRequest($serverId, $siteId));
    }

    public function deploy(int $serverId, int $siteId): void
    {
        $this->connector->send(new DeploySiteRequest($serverId, $siteId));
    }

    public function deploymentScript(int $serverId, int $siteId): string
    {
        $response = $this->connector->send(new GetDeploymentScriptRequest($serverId, $siteId));

        return $response->body();
    }

    public function updateDeploymentScript(int $serverId, int $siteId, string $content): void
    {
        $this->connector->send(new UpdateDeploymentScriptRequest($serverId, $siteId, $content));
    }

    public function deploymentHistory(int $serverId, int $siteId): array
    {
        $response = $this->connector->send(new GetDeploymentHistoryRequest($serverId, $siteId));

        return $response->json('deployments', []);
    }

    public function deploymentHistoryDeployment(int $serverId, int $siteId, int $deploymentId): array
    {
        $response = $this->connector->send(new GetDeploymentHistoryDeploymentRequest($serverId, $siteId, $deploymentId));

        return $response->json('deployment', []);
    }

    public function commandHistory(int $serverId, int $siteId, ?string $cursor = null, int $pageSize = 30): array
    {
        $response = $this->connector->send(new ListCommandHistoryRequest($serverId, $siteId, $cursor, $pageSize));

        return $response->json('commands', []);
    }

    public function getCommand(int $serverId, int $siteId, int $commandId): array
    {
        $response = $this->connector->send(new GetSiteCommandRequest($serverId, $siteId, $commandId));

        return $response->json('command', []);
    }

    public function executeCommand(int $serverId, int $siteId, ExecuteSiteCommandData $data): array
    {
        $request = new ExecuteSiteCommandRequest($serverId, $siteId, $data);
        $response = $this->connector->send($request);

        return $response->json('command', []);
    }

    public function installGitRepository(int $serverId, int $siteId, InstallGitRepositoryData $data): SiteData
    {
        $request = new InstallGitRepositoryRequest($serverId, $siteId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function updateGitRepository(int $serverId, int $siteId, UpdateGitRepositoryData $data): void
    {
        $this->connector->send(new UpdateGitRepositoryRequest($serverId, $siteId, $data));
    }

    public function destroyGitRepository(int $serverId, int $siteId): void
    {
        $this->connector->send(new DestroyGitRepositoryRequest($serverId, $siteId));
    }

    public function createDeployKey(int $serverId, int $siteId): array
    {
        $response = $this->connector->send(new CreateDeployKeyRequest($serverId, $siteId));

        return $response->json();
    }

    public function deleteDeployKey(int $serverId, int $siteId): void
    {
        $this->connector->send(new DeleteDeployKeyRequest($serverId, $siteId));
    }

    public function changePhpVersion(int $serverId, int $siteId, string $version): void
    {
        $request = new \App\Integrations\Forge\Requests\Sites\ChangePhpVersionRequest($serverId, $siteId, $version);
        $this->connector->send($request);
    }

    public function getNginxConfig(int $serverId, int $siteId): string
    {
        $response = $this->connector->send(new \App\Integrations\Forge\Requests\Sites\GetNginxConfigRequest($serverId, $siteId));

        return $response->body();
    }

    public function updateNginxConfig(int $serverId, int $siteId, string $content): void
    {
        $this->connector->send(new \App\Integrations\Forge\Requests\Sites\UpdateNginxConfigRequest($serverId, $siteId, $content));
    }

    public function getEnvFile(int $serverId, int $siteId): string
    {
        $response = $this->connector->send(new \App\Integrations\Forge\Requests\Sites\GetEnvFileRequest($serverId, $siteId));

        return $response->body();
    }

    public function updateEnvFile(int $serverId, int $siteId, string $content): void
    {
        $this->connector->send(new \App\Integrations\Forge\Requests\Sites\UpdateEnvFileRequest($serverId, $siteId, $content));
    }

    public function installWordPress(int $serverId, int $siteId, string $database, string $user, ?string $password = null): void
    {
        $this->connector->send(new \App\Integrations\Forge\Requests\Sites\InstallWordPressRequest($serverId, $siteId, $database, $user, $password));
    }

    public function uninstallWordPress(int $serverId, int $siteId): void
    {
        $this->connector->send(new \App\Integrations\Forge\Requests\Sites\UninstallWordPressRequest($serverId, $siteId));
    }

    public function installPhpMyAdmin(int $serverId, int $siteId): void
    {
        $this->connector->send(new \App\Integrations\Forge\Requests\Sites\InstallPhpMyAdminRequest($serverId, $siteId));
    }

    public function uninstallPhpMyAdmin(int $serverId, int $siteId): void
    {
        $this->connector->send(new \App\Integrations\Forge\Requests\Sites\UninstallPhpMyAdminRequest($serverId, $siteId));
    }
}
