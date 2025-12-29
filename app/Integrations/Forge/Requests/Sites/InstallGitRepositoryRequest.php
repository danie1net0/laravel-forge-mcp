<?php

namespace App\Integrations\Forge\Requests\Sites;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use App\Integrations\Forge\Data\Sites\{InstallGitRepositoryData, SiteData};
use Saloon\Traits\Body\HasJsonBody;

class InstallGitRepositoryRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected InstallGitRepositoryData $data
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/git";
    }

    public function createDtoFromResponse(Response $response): SiteData
    {
        return SiteData::from(array_merge($response->json('site'), ['server_id' => $this->serverId]));
    }

    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
