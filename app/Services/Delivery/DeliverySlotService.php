<?php

namespace App\Services\Delivery;

use App\Models\DeliverySlotWindow;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DeliverySlotService
{
    public function calendar(int $days = 5, int $leadMinutes = 45): array
    {
        $windows = DeliverySlotWindow::query()
            ->active()
            ->ordered()
            ->get()
            ->groupBy('weekday');

        $now = now();
        $items = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $now->copy()->startOfDay()->addDays($offset);
            $weekday = (int) $date->dayOfWeek;
            $dayWindows = $windows->get($weekday, collect());
            $slots = $this->slotsForDate($date, $dayWindows, $now, $leadMinutes);

            $items[] = [
                'date' => $date->toDateString(),
                'label' => $this->dayLabel($date, $offset),
                'weekday' => $weekday,
                'weekday_label' => DeliverySlotWindow::weekdayNames()[$weekday] ?? '',
                'slots' => $slots,
            ];
        }

        return [
            'now_available' => true,
            'now_label' => 'الآن',
            'days' => $items,
        ];
    }

    /**
     * @param  Collection<int, DeliverySlotWindow>  $windows
     * @return list<array{id: string, start: string, end: string, label: string, available: bool, starts_at: string}>
     */
    private function slotsForDate(Carbon $date, Collection $windows, Carbon $now, int $leadMinutes): array
    {
        $slots = [];
        foreach ($windows as $window) {
            $start = Carbon::parse($date->toDateString().' '.$window->start_time);
            $end = Carbon::parse($date->toDateString().' '.$window->end_time);
            $available = $start->greaterThan($now->copy()->addMinutes($leadMinutes));
            $startLabel = $start->format('H:i');
            $endLabel = $end->format('H:i');

            $slots[] = [
                'id' => $date->toDateString().'_'.$startLabel,
                'start' => $startLabel,
                'end' => $endLabel,
                'label' => $startLabel.' - '.$endLabel,
                'available' => $available,
                'starts_at' => $start->toIso8601String(),
            ];
        }

        return $slots;
    }

    private function dayLabel(Carbon $date, int $offset): string
    {
        return match ($offset) {
            0 => 'اليوم',
            1 => 'غداً',
            default => DeliverySlotWindow::weekdayNames()[(int) $date->dayOfWeek] ?? $date->translatedFormat('l'),
        };
    }
}
