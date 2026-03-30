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
        private readonly ?string $cursor = null,
        private readonly int $pageSize = 30,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/domains";
    }

    public function createDtoFromResponse(Response $response): CertificateCollectionData
    {
        $domains = $response->json('domains', []);

        $domainsWithContext = array_map(
            fn (array $domain): array => array_merge($domain, [
                'server_id' => $this->serverId,
                'site_id' => $this->siteId,
            ]),
            $domains
        );

        return CertificateCollectionData::from([
            'certificates' => $domainsWithContext,
        ]);
    }

    /**
     * @return array<string, string|int>
     */
    protected function defaultQuery(): array
    {
        $query = ['page[size]' => $this->pageSize];

        if ($this->cursor !== null) {
            $query['page[cursor]'] = $this->cursor;
        }

        return $query;
    }
}
