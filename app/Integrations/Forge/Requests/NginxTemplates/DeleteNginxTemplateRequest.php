<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\NginxTemplates;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteNginxTemplateRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $serverId,
        private readonly int $templateId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/nginx/templates/{$this->templateId}";
    }
}
