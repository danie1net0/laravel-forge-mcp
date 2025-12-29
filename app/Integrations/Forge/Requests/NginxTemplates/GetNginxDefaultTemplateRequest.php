<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\NginxTemplates;

use App\Integrations\Forge\Data\NginxTemplates\NginxTemplateData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetNginxDefaultTemplateRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/nginx/templates/default";
    }

    public function createDtoFromResponse(Response $response): NginxTemplateData
    {
        return NginxTemplateData::from(array_merge($response->json('template'), ['server_id' => $this->serverId]));
    }
}
