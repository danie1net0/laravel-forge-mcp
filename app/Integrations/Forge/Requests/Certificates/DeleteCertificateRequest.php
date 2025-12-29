<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Certificates;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteCertificateRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected int $certificateId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/certificates/{$this->certificateId}";
    }
}
