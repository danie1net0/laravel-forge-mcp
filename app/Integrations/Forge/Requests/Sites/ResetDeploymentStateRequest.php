<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Sites;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ResetDeploymentStateRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected int $siteId
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/deployment/reset";
    }
}
