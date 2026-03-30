<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Integrations;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class EnableMaintenanceRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected ?string $secret = null,
        protected int $status = 503
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/integrations/laravel-maintenance";
    }

    /**
     * @return array{status: int, secret?: string}
     */
    protected function defaultBody(): array
    {
        $data = [
            'status' => $this->status,
        ];

        if ($this->secret !== null) {
            $data['secret'] = $this->secret;
        }

        return $data;
    }
}
