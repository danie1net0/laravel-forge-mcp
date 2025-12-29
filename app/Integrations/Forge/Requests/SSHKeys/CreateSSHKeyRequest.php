<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\SSHKeys;

use App\Integrations\Forge\Data\SSHKeys\{CreateSSHKeyData, SSHKeyData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class CreateSSHKeyRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $serverId,
        private readonly CreateSSHKeyData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/keys";
    }

    public function createDtoFromResponse(Response $response): SSHKeyData
    {
        return SSHKeyData::from(array_merge($response->json('key'), ['server_id' => $this->serverId]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
