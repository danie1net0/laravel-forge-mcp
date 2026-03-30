<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\Data\Certificates\{CertificateCollectionData, CertificateData};
use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Requests\Certificates\{ActivateCertificateRequest, DeleteCertificateRequest, GetCertificateRequest, GetSigningRequestRequest, ListCertificatesRequest, ObtainLetsEncryptCertificateRequest};

class CertificateResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId, int $siteId, ?string $cursor = null, int $pageSize = 30): CertificateCollectionData
    {
        $request = new ListCertificatesRequest($serverId, $siteId, $cursor, $pageSize);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $siteId, int $domainId): CertificateData
    {
        $request = new GetCertificateRequest($serverId, $siteId, $domainId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function obtainLetsEncrypt(int $serverId, int $siteId, int $domainId): CertificateData
    {
        $request = new ObtainLetsEncryptCertificateRequest($serverId, $siteId, $domainId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function activate(int $serverId, int $siteId, int $domainId): void
    {
        $this->connector->send(new ActivateCertificateRequest($serverId, $siteId, $domainId));
    }

    public function delete(int $serverId, int $siteId, int $domainId): void
    {
        $this->connector->send(new DeleteCertificateRequest($serverId, $siteId, $domainId));
    }

    public function signingRequest(int $serverId, int $siteId, int $domainId): string
    {
        $response = $this->connector->send(new GetSigningRequestRequest($serverId, $siteId, $domainId));

        return $response->body();
    }
}
