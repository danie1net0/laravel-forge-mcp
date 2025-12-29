<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Php;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class InstallPhpRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected string $version
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/php";
    }

    protected function defaultBody(): array
    {
        return [
            'version' => $this->version,
        ];
    }
}
