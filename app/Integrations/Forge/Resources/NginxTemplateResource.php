<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\NginxTemplates\{CreateNginxTemplateData, NginxTemplateCollectionData, NginxTemplateData, UpdateNginxTemplateData};
use App\Integrations\Forge\Requests\NginxTemplates\{CreateNginxTemplateRequest, DeleteNginxTemplateRequest, GetNginxDefaultTemplateRequest, GetNginxTemplateRequest, ListNginxTemplatesRequest, UpdateNginxTemplateRequest};

class NginxTemplateResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId): NginxTemplateCollectionData
    {
        $request = new ListNginxTemplatesRequest($serverId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $templateId): NginxTemplateData
    {
        $request = new GetNginxTemplateRequest($serverId, $templateId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function default(int $serverId): NginxTemplateData
    {
        $request = new GetNginxDefaultTemplateRequest($serverId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function create(int $serverId, CreateNginxTemplateData $data): NginxTemplateData
    {
        $request = new CreateNginxTemplateRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function update(int $serverId, int $templateId, UpdateNginxTemplateData $data): NginxTemplateData
    {
        $request = new UpdateNginxTemplateRequest($serverId, $templateId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, int $templateId): void
    {
        $this->connector->send(new DeleteNginxTemplateRequest($serverId, $templateId));
    }
}
