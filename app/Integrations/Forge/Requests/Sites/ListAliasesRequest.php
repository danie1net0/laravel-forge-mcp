<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Requests\Sites;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ListAliasesRequest extends Request
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
        return "/servers/{$this->serverId}/sites/{$this->siteId}/aliases";
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
