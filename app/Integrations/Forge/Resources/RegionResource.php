<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Resources;

use App\Integrations\Forge\ForgeConnector;
use Saloon\Enums\Method;
use Saloon\Http\Request;

class RegionResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function list(): array
    {
        $request = new class() extends Request {
            protected Method $method = Method::GET;

            public function resolveEndpoint(): string
            {
                return '/regions';
            }
        };

        $response = $this->connector->send($request);

        return $response->json('regions', []);
    }
}
