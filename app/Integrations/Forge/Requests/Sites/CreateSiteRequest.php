<?php

namespace App\Integrations\Forge\Requests\Sites;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use App\Integrations\Forge\Data\Sites\{CreateSiteData, SiteData};
use Saloon\Traits\Body\HasJsonBody;

class CreateSiteRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected CreateSiteData $data
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites";
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
