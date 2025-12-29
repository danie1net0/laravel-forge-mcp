<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Backups\{BackupConfigurationCollectionData, BackupConfigurationData, CreateBackupConfigurationData, UpdateBackupConfigurationData};
use App\Integrations\Forge\Requests\Backups\{CreateBackupConfigurationRequest, DeleteBackupConfigurationRequest, DeleteBackupRequest, GetBackupConfigurationRequest, ListBackupConfigurationsRequest, RestoreBackupRequest, UpdateBackupConfigurationRequest};

class BackupResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function listConfigurations(int $serverId): BackupConfigurationCollectionData
    {
        $request = new ListBackupConfigurationsRequest($serverId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function getConfiguration(int $serverId, int $backupId): BackupConfigurationData
    {
        $request = new GetBackupConfigurationRequest($serverId, $backupId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function createConfiguration(int $serverId, CreateBackupConfigurationData $data): BackupConfigurationData
    {
        $request = new CreateBackupConfigurationRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function updateConfiguration(int $serverId, int $backupId, UpdateBackupConfigurationData $data): BackupConfigurationData
    {
        $request = new UpdateBackupConfigurationRequest($serverId, $backupId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function deleteConfiguration(int $serverId, int $backupId): void
    {
        $this->connector->send(new DeleteBackupConfigurationRequest($serverId, $backupId));
    }

    public function restore(int $serverId, int $backupConfigId, int $backupId): void
    {
        $this->connector->send(new RestoreBackupRequest($serverId, $backupConfigId, $backupId));
    }

    public function delete(int $serverId, int $backupConfigId, int $backupId): void
    {
        $this->connector->send(new DeleteBackupRequest($serverId, $backupConfigId, $backupId));
    }
}
