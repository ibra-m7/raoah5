<?php

namespace App\Services\Couriers;

use App\Models\Courier;
use App\Models\CourierLedgerEntry;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourierLedgerService
{
    /**
     * @return array{collected: float, settled: float, owes: float, owed: float, net: float}
     */
    public function summary(Courier $courier): array
    {
        $debit = (float) $courier->ledgerEntries()->where('direction', CourierLedgerEntry::DIRECTION_DEBIT)->sum('amount');
        $credit = (float) $courier->ledgerEntries()->where('direction', CourierLedgerEntry::DIRECTION_CREDIT)->sum('amount');
        $net = round($debit - $credit, 2);

        return [
            'collected' => round($debit, 2),
            'settled' => round($credit, 2),
            'owes' => round(max(0, $net), 2),
            'owed' => round(max(0, -$net), 2),
            'net' => $net,
        ];
    }

    public function recordCod(Order $order): void
    {
        if ($order->courier_id === null || ! $order->isCashOnDelivery()) {
            return;
        }

        CourierLedgerEntry::query()->firstOrCreate(
            [
                'order_id' => $order->id,
                'type' => CourierLedgerEntry::TYPE_COD,
            ],
            [
                'courier_id' => $order->courier_id,
                'direction' => CourierLedgerEntry::DIRECTION_DEBIT,
                'amount' => round((float) $order->total, 2),
                'note' => 'تحصيل طلب '.$order->order_number,
            ],
        );
    }

    public function settle(Courier $courier, float $amount, ?string $note = null, ?User $admin = null): CourierLedgerEntry
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'أدخل مبلغاً أكبر من صفر.',
            ]);
        }

        return DB::transaction(function () use ($courier, $amount, $note, $admin) {
            $locked = Courier::query()->lockForUpdate()->findOrFail($courier->id);
            $summary = $this->summary($locked);
            if ($amount - $summary['owes'] > 0.009) {
                throw ValidationException::withMessages([
                    'amount' => 'المبلغ أكبر من مديونية الموصل ('.number_format($summary['owes'], 2).').',
                ]);
            }

            return CourierLedgerEntry::query()->create([
                'courier_id' => $locked->id,
                'order_id' => null,
                'type' => CourierLedgerEntry::TYPE_SETTLEMENT,
                'direction' => CourierLedgerEntry::DIRECTION_CREDIT,
                'amount' => $amount,
                'note' => $note ?: 'تسديد من لوحة التحكم',
                'created_by' => $admin?->id,
            ]);
        });
    }
}
