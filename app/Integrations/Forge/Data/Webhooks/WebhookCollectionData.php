<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Webhooks;

use Spatie\LaravelData\Data;

class WebhookCollectionData extends Data
{
    /**
     * @param  WebhookData[]  $webhooks
     */
    public function __construct(
        public array $webhooks,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $webhooks = array_map(
            fn (array $webhook): WebhookData => WebhookData::from($webhook),
            $data['webhooks'] ?? []
        );

        return new self(webhooks: $webhooks);
    }
}
