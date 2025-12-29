<?php

namespace App\Integrations\Forge\Requests\Sites;

use App\Integrations\Forge\Data\Sites\SiteCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListSitesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites";
    }

    public function createDtoFromResponse(Response $response): SiteCollectionData
    {
        $sites = array_map(
            fn (array $site): array => array_merge($site, ['server_id' => $this->serverId]),
            $response->json('sites')
        );

        return SiteCollectionData::from(['sites' => $sites]);
    }
}
