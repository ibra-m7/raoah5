<?php

namespace App\Services\Delivery;

use App\Enums\DeliveryPerKmMode;
use App\Enums\DeliveryPricingType;
use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\DeliveryPerk;
use App\Models\DeliveryRule;
use App\Models\User;
use App\Support\DeliverySettings;
use App\Support\StoreSettings;
use Illuminate\Validation\ValidationException;

class DeliveryService
{
    public function quote(
        ?User $user,
        ?Address $address,
        float $subtotal,
        bool $couponFreeShipping = false,
        ?int $exceptOrderId = null,
    ): DeliveryQuote {
        $distance = $this->distanceKm($address);
        $matchedRule = DeliverySettings::enabled() && $distance !== null
            ? $this->matchRule($distance)
            : null;

        if ($couponFreeShipping) {
            return $this->withNotes(DeliveryQuote::free('كوبون توصيل مجاني', $distance), $matchedRule);
        }

        if (DeliverySettings::firstOrderFree() && $user && $this->isFirstOrder($user, $exceptOrderId)) {
            return $this->withNotes(DeliveryQuote::free('أول طلب مجاني', $distance), $matchedRule);
        }

        $threshold = StoreSettings::freeShippingThreshold();
        if ($threshold > 0 && $subtotal >= $threshold) {
            return $this->withNotes(DeliveryQuote::free('تجاوزت حد التوصيل المجاني للطلب', $distance), $matchedRule);
        }

        if (! DeliverySettings::enabled()) {
            $fee = StoreSettings::shippingFee();

            return $this->withPerks($this->withNotes(new DeliveryQuote(
                $fee,
                $fee <= 0,
                $distance,
                $fee <= 0 ? 'توصيل مجاني' : 'رسوم التوصيل الثابتة',
            )), $user, $exceptOrderId);
        }

        $maxKm = DeliverySettings::maxKm();
        if ($maxKm !== null && $distance !== null && $distance > $maxKm) {
            throw ValidationException::withMessages([
                'address_id' => 'العنوان خارج نطاق التوصيل. الحد الأقصى '.$this->fmt($maxKm).' كم من المتجر.',
            ]);
        }

        if ($distance === null) {
            $fee = DeliverySettings::fallbackFee();

            return $this->withPerks($this->withNotes(new DeliveryQuote(
                $fee,
                $fee <= 0,
                null,
                $fee <= 0 ? 'توصيل مجاني' : 'رسوم احتياطية — حدّد موقع المتجر وعنوان العميل لحساب المسافة',
            )), $user, $exceptOrderId);
        }

        $rule = $matchedRule;
        if ($rule === null) {
            $fee = DeliverySettings::fallbackFee();

            return $this->withPerks($this->withNotes(new DeliveryQuote(
                $fee,
                $fee <= 0,
                $distance,
                'لا توجد شريحة مسافة مطابقة — الرسوم الاحتياطية',
            )), $user, $exceptOrderId);
        }

        return $this->withPerks($this->withNotes($this->applyRule($rule, $distance), $rule), $user, $exceptOrderId);
    }

    private function withNotes(DeliveryQuote $quote, ?DeliveryRule $rule = null): DeliveryQuote
    {
        if (! DeliverySettings::notesEnabled()) {
            return $quote->withNote(null);
        }

        $parts = [];
        $general = DeliverySettings::generalNote();
        if ($general !== '') {
            $parts[] = $general;
        }
        if ($rule !== null && $rule->note_enabled) {
            $ruleNote = trim((string) ($rule->note ?? ''));
            if ($ruleNote !== '') {
                $parts[] = $ruleNote;
            }
        }

        return $quote->withNote($parts === [] ? null : implode("\n", $parts));
    }

    private function applyRule(DeliveryRule $rule, float $distance): DeliveryQuote
    {
        $fee = match ($rule->pricing_type) {
            DeliveryPricingType::Free => 0.0,
            DeliveryPricingType::Flat => round(max(0, (float) $rule->amount), 2),
            DeliveryPricingType::PerKm => $this->perKmFee($rule, $distance),
        };

        $label = $rule->name;
        if ($rule->pricing_type === DeliveryPricingType::PerKm) {
            $label .= ' — '.$this->fmt($distance).' كم';
        }

        return new DeliveryQuote(
            $fee,
            $fee <= 0,
            $distance,
            $fee <= 0 ? ($rule->name.' (مجاني)') : $label,
            $rule->id,
            $rule->name,
        );
    }

    private function perKmFee(DeliveryRule $rule, float $distance): float
    {
        $billable = $rule->per_km_mode === DeliveryPerKmMode::Extra
            ? max(0, $distance - (float) $rule->min_km)
            : $distance;

        return round(max(0, $billable * (float) $rule->amount), 2);
    }

    private function matchRule(float $distance): ?DeliveryRule
    {
        return DeliveryRule::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('min_km')
            ->get()
            ->first(fn (DeliveryRule $rule) => $rule->matches($distance));
    }

    public function distanceKm(?Address $address): ?float
    {
        $storeLat = DeliverySettings::storeLat();
        $storeLng = DeliverySettings::storeLng();
        if ($storeLat === null || $storeLng === null || $address === null) {
            return null;
        }
        if ($address->latitude === null || $address->longitude === null) {
            return null;
        }

        return $this->haversine(
            $storeLat,
            $storeLng,
            (float) $address->latitude,
            (float) $address->longitude,
        );
    }

    private function withPerks(DeliveryQuote $quote, ?User $user, ?int $exceptOrderId): DeliveryQuote
    {
        if ($quote->isFree || $user === null) {
            return $quote;
        }

        $completed = $this->completedOrderCount($user, $exceptOrderId);
        $upcoming = $completed + 1;
        $perk = DeliveryPerk::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->first(fn (DeliveryPerk $item) => $item->matches($completed, $upcoming));

        if ($perk === null) {
            return $quote;
        }

        $nextFee = $perk->applyToFee($quote->fee);
        $discount = round(max(0, $quote->fee - $nextFee), 2);
        $label = $nextFee <= 0
            ? $perk->name
            : $quote->label.' — '.$perk->name;

        return $quote->withPerk($nextFee, $label, $perk->id, $perk->name, $discount);
    }

    public function completedOrderCount(User $user, ?int $exceptOrderId = null): int
    {
        return $user->orders()
            ->where('status', '!=', OrderStatus::Cancelled)
            ->when($exceptOrderId, fn ($query) => $query->where('id', '!=', $exceptOrderId))
            ->count();
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earth * $c, 2);
    }

    private function isFirstOrder(User $user, ?int $exceptOrderId): bool
    {
        return ! $user->orders()
            ->where('status', '!=', OrderStatus::Cancelled)
            ->when($exceptOrderId, fn ($query) => $query->where('id', '!=', $exceptOrderId))
            ->exists();
    }

    private function fmt(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2), '0'), '.');
    }
}
