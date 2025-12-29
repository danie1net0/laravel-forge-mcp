<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Services;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class InstallPapertrailRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected string $host
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/papertrail/install";
    }

    protected function defaultBody(): array
    {
        return [
            'host' => $this->host,
        ];
    }
}
