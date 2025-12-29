<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Sites;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class InstallWordPressRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected string $database,
        protected string $user,
        protected ?string $password = null
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/wordpress";
    }

    protected function defaultBody(): array
    {
        $body = [
            'database' => $this->database,
            'user' => $this->user,
        ];

        if ($this->password !== null) {
            $body['password'] = $this->password;
        }

        return $body;
    }
}
