<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\SecurityRules;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteSecurityRuleRequest extends Request
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
        return "/servers/{$this->serverId}/sites/{$this->siteId}/security-rules/{$this->ruleId}";
    }
}
