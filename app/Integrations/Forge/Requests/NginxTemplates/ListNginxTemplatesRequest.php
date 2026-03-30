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
        private readonly ?string $cursor = null,
        private readonly int $pageSize = 30,
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

    /**
     * @return array<string, string|int>
     */
    protected function defaultQuery(): array
    {
        $query = ['page[size]' => $this->pageSize];

        if ($this->cursor !== null) {
            $query['page[cursor]'] = $this->cursor;
        }

        return $query;
    }
}
