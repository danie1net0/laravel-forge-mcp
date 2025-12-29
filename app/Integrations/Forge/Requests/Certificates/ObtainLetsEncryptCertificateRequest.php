<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Certificates;

use App\Integrations\Forge\Data\Certificates\{CertificateData, ObtainLetsEncryptCertificateData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class ObtainLetsEncryptCertificateRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected ObtainLetsEncryptCertificateData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/certificates/letsencrypt";
    }

    public function createDtoFromResponse(Response $response): CertificateData
    {
        return CertificateData::from(array_merge($response->json('certificate'), ['server_id' => $this->serverId, 'site_id' => $this->siteId]));
    }

    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
