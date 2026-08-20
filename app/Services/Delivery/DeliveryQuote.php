<?php

namespace App\Services\Delivery;

final class DeliveryQuote
{
    public function __construct(
        public readonly float $fee,
        public readonly bool $isFree,
        public readonly ?float $distanceKm,
        public readonly string $label,
        public readonly ?int $ruleId = null,
        public readonly ?string $ruleName = null,
        public readonly float $originalFee = 0.0,
        public readonly ?int $perkId = null,
        public readonly ?string $perkName = null,
        public readonly float $perkDiscount = 0.0,
    ) {}

    public static function free(
        string $label,
        ?float $distanceKm = null,
        ?int $ruleId = null,
        ?string $ruleName = null,
    ): self {
        return new self(0.0, true, $distanceKm, $label, $ruleId, $ruleName);
    }

    public function withPerk(float $fee, string $label, int $perkId, string $perkName, float $discount): self
    {
        return new self(
            $fee,
            $fee <= 0,
            $this->distanceKm,
            $label,
            $this->ruleId,
            $this->ruleName,
            $this->originalFee > 0 ? $this->originalFee : $this->fee,
            $perkId,
            $perkName,
            $discount,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'fee' => $this->fee,
            'is_free' => $this->isFree,
            'distance_km' => $this->distanceKm,
            'label' => $this->label,
            'rule_id' => $this->ruleId,
            'rule_name' => $this->ruleName,
            'original_fee' => $this->originalFee > 0 ? $this->originalFee : $this->fee,
            'perk_id' => $this->perkId,
            'perk_name' => $this->perkName,
            'perk_discount' => $this->perkDiscount,
        ];
    }
}
