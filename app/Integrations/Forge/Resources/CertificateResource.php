<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use App\Integrations\Forge\Data\Certificates\{CertificateCollectionData, CertificateData, ObtainLetsEncryptCertificateData};
use App\Integrations\Forge\Requests\Certificates\{ActivateCertificateRequest, DeleteCertificateRequest, GetCertificateRequest, GetSigningRequestRequest, ListCertificatesRequest, ObtainLetsEncryptCertificateRequest};

class CertificateResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(int $serverId, int $siteId): CertificateCollectionData
    {
        $request = new ListCertificatesRequest($serverId, $siteId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $siteId, int $certificateId): CertificateData
    {
        $request = new GetCertificateRequest($serverId, $siteId, $certificateId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function obtainLetsEncrypt(int $serverId, int $siteId, ObtainLetsEncryptCertificateData $data): CertificateData
    {
        $request = new ObtainLetsEncryptCertificateRequest($serverId, $siteId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function activate(int $serverId, int $siteId, int $certificateId): void
    {
        $this->connector->send(new ActivateCertificateRequest($serverId, $siteId, $certificateId));
    }

    public function delete(int $serverId, int $siteId, int $certificateId): void
    {
        $this->connector->send(new DeleteCertificateRequest($serverId, $siteId, $certificateId));
    }

    public function signingRequest(int $serverId, int $siteId, int $certificateId): string
    {
        $response = $this->connector->send(new GetSigningRequestRequest($serverId, $siteId, $certificateId));

        return $response->body();
    }
}
