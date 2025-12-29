<?php

declare(strict_types=1);

namespace App\Integrations\Forge\Data\RedirectRules;

use Spatie\LaravelData\Data;

class RedirectRuleCollectionData extends Data
{
    /**
     * @param  RedirectRuleData[]  $rules
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
            fn (array $rule): RedirectRuleData => RedirectRuleData::from($rule),
            $data['rules'] ?? []
        );

        return new self(rules: $rules);
    }
}
