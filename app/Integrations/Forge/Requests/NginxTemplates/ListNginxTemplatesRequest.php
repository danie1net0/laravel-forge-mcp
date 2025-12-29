<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\NginxTemplates;

use App\Integrations\Forge\Data\NginxTemplates\NginxTemplateCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListNginxTemplatesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/nginx/templates";
    }

    public function createDtoFromResponse(Response $response): NginxTemplateCollectionData
    {
        $templates = array_map(
            fn (array $template): array => array_merge($template, ['server_id' => $this->serverId]),
            $response->json('templates')
        );

        return NginxTemplateCollectionData::from(['templates' => $templates]);
    }
}
