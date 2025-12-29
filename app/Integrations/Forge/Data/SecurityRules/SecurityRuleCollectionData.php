<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\SecurityRules;

use Spatie\LaravelData\Data;

class SecurityRuleCollectionData extends Data
{
    /**
     * @param  SecurityRuleData[]  $rules
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
            fn (array $rule): SecurityRuleData => SecurityRuleData::from($rule),
            $data['rules'] ?? []
        );

        return new self(rules: $rules);
    }
}
