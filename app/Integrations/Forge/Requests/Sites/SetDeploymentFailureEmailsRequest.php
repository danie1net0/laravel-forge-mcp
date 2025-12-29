<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Sites;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class SetDeploymentFailureEmailsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param array<int, string> $emails
     */
    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected array $emails
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/deployment-failure-emails";
    }

    protected function defaultBody(): array
    {
        return [
            'emails' => $this->emails,
        ];
    }
}
