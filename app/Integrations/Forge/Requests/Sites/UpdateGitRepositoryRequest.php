<?php

namespace App\Integrations\Forge\Requests\Sites;

use App\Integrations\Forge\Data\Sites\UpdateGitRepositoryData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdateGitRepositoryRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected UpdateGitRepositoryData $data
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/git";
    }

    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
