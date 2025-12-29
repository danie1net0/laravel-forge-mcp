<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\RedirectRules;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteRedirectRuleRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly int $serverId,
        private readonly int $siteId,
        private readonly int $ruleId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/redirect-rules/{$this->ruleId}";
    }
}
