<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Servers;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdateDatabasePasswordRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        private readonly int $serverId,
        private readonly ?string $password = null,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/database/password";
    }

    /** @return array<string, string> */
    protected function defaultBody(): array
    {
        if ($this->password === null) {
            return [];
        }

        return ['password' => $this->password];
    }
}
