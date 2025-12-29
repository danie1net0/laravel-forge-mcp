<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\NginxTemplates;

use App\Integrations\Forge\Data\NginxTemplates\NginxTemplateData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetNginxTemplateRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
        private readonly int $templateId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/nginx/templates/{$this->templateId}";
    }

    public function createDtoFromResponse(Response $response): NginxTemplateData
    {
        return NginxTemplateData::from(array_merge($response->json('template'), ['server_id' => $this->serverId]));
    }
}
