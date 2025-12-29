<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\NginxTemplates;

use App\Integrations\Forge\Data\NginxTemplates\{CreateNginxTemplateData, NginxTemplateData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class CreateNginxTemplateRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $serverId,
        private readonly CreateNginxTemplateData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/nginx/templates";
    }

    public function createDtoFromResponse(Response $response): NginxTemplateData
    {
        return NginxTemplateData::from(array_merge($response->json('template'), ['server_id' => $this->serverId]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
