<?php

namespace App\Integrations\Forge\Requests\Sites;

use App\Integrations\Forge\Data\Sites\ExecuteSiteCommandData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class ExecuteSiteCommandRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected ExecuteSiteCommandData $data
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/commands";
    }

    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
