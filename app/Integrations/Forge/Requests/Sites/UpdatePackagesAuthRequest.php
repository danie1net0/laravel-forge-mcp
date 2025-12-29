<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Sites;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdatePackagesAuthRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    /**
     * @param array<string, mixed> $packages
     */
    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected array $packages
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/packages";
    }

    protected function defaultBody(): array
    {
        return [
            'packages' => $this->packages,
        ];
    }
}
