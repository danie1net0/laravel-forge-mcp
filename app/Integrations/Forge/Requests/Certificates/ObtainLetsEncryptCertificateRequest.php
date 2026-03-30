<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Certificates;

use App\Integrations\Forge\Data\Certificates\CertificateData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class ObtainLetsEncryptCertificateRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly int $serverId,
        private readonly int $siteId,
        private readonly int $domainId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/domains/{$this->domainId}/certificate/actions";
    }

    public function createDtoFromResponse(Response $response): CertificateData
    {
        return CertificateData::from(array_merge((array) $response->json('certificate'), ['server_id' => $this->serverId, 'site_id' => $this->siteId]));
    }

    /**
     * @return array{action: string}
     */
    protected function defaultBody(): array
    {
        return ['action' => 'enable'];
    }
}
