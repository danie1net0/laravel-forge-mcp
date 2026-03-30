<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Certificates;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class ActivateCertificateRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected int $domainId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/domains/{$this->domainId}/certificate/actions";
    }

    /**
     * @return array{action: string}
     */
    protected function defaultBody(): array
    {
        return ['action' => 'enable'];
    }
}
