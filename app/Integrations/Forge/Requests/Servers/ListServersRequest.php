<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Servers;

use App\Integrations\Forge\Data\Servers\ServerCollectionData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListServersRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly ?string $cursor = null,
        private readonly int $pageSize = 30,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/servers';
    }

    public function createDtoFromResponse(Response $response): ServerCollectionData
    {
        return ServerCollectionData::from($response->json());
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
