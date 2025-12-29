<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\Firewall;

use Spatie\LaravelData\Data;

class FirewallRuleCollectionData extends Data
{
    /**
     * @param  FirewallRuleData[]  $rules
     */
    public function __construct(
        public array $rules,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $rules = array_map(
            fn (array $rule): FirewallRuleData => FirewallRuleData::from($rule),
            $data['rules'] ?? []
        );

        return new self(rules: $rules);
    }
}
