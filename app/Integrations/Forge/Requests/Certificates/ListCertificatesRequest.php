<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Certificates;

use App\Integrations\Forge\Data\Certificates\CertificateCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListCertificatesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/certificates";
    }

    public function createDtoFromResponse(Response $response): CertificateCollectionData
    {
        $certificates = $response->json('certificates', []);

        $certificatesWithContext = array_map(
            fn (array $cert) => array_merge($cert, [
                'server_id' => $this->serverId,
                'site_id' => $this->siteId,
            ]),
            $certificates
        );

        return CertificateCollectionData::from([
            'certificates' => $certificatesWithContext,
        ]);
    }
}
