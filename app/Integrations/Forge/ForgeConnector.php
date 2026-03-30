<?php

declare(strict_types=1);

namespace App\Integrations\Forge;

use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\{AcceptsJson, AlwaysThrowOnErrors};

class ForgeConnector extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;

    public bool $allowBaseUrlOverride = true;

    protected ?string $response = ForgeResponse::class;

    public function __construct(
        protected string $apiToken,
        protected string $organization,
    ) {
    }

    public function resolveBaseUrl(): string
    {
        return "https://forge.laravel.com/api/orgs/{$this->organization}";
    }

    /** @return array<string, string> */
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator($this->apiToken);
    }
}
