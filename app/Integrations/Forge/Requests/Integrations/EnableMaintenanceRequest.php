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
        protected ?string $refresh = null
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/integrations/laravel-maintenance";
    }

    protected function defaultBody(): array
    {
        $data = [];

        if ($this->secret !== null) {
            $data['secret'] = $this->secret;
        }

        if ($this->refresh !== null) {
            $data['refresh'] = $this->refresh;
        }

        return $data;
    }
}
