<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Services;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class ManageServiceRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected string $action,
        protected string $service
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/services/{$this->action}";
    }

    protected function defaultBody(): array
    {
        return [
            'service' => $this->service,
        ];
    }
}
