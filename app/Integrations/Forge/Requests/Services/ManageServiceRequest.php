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
        private readonly int $serverId,
        private readonly string $service,
        private readonly string $action,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/services/{$this->service}/actions";
    }

    /**
     * @return array{action: string}
     */
    protected function defaultBody(): array
    {
        return [
            'action' => $this->action,
        ];
    }
}
