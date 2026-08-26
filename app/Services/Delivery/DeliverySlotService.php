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

        $configuredWeekdays = $windows
            ->filter(fn (Collection $dayWindows) => $dayWindows->isNotEmpty())
            ->keys()
            ->map(fn ($weekday) => (int) $weekday)
            ->all();

        $now = now();
        $items = [];
        $offset = 0;
        $scanned = 0;
        $maxScan = 21;

        while (count($items) < $days && $scanned < $maxScan) {
            $date = $now->copy()->startOfDay()->addDays($offset);
            $weekday = (int) $date->dayOfWeek;
            $scanned++;
            $offset++;

            if ($configuredWeekdays !== [] && ! in_array($weekday, $configuredWeekdays, true)) {
                continue;
            }

            $dayWindows = $windows->get($weekday, collect());
            if ($dayWindows->isEmpty()) {
                continue;
            }

            $slots = $this->slotsForDate($date, $dayWindows, $now, $leadMinutes);
            if ($slots === []) {
                continue;
            }

            $items[] = [
                'date' => $date->toDateString(),
                'label' => $this->dayLabel($date, (int) $now->copy()->startOfDay()->diffInDays($date)),
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
     * @return list<array{id: string, start: string, end: string, label: string, period: string, available: bool, starts_at: string}>
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
            $period = $this->periodForHour((int) $start->hour);
            $suffix = $period === 'morning' ? 'ص' : 'م';

            $slots[] = [
                'id' => $date->toDateString().'_'.$startLabel,
                'start' => $startLabel,
                'end' => $endLabel,
                'label' => $this->formatClock($start).' - '.$this->formatClock($end).' '.$suffix,
                'period' => $period,
                'available' => $available,
                'starts_at' => $start->toIso8601String(),
            ];
        }

        usort($slots, static function (array $a, array $b): int {
            return strcmp($a['start'], $b['start']);
        });

        return $slots;
    }

    /** صباح قبل الظهر، مساء من 12:00 فصاعداً. */
    private function periodForHour(int $hour): string
    {
        return $hour < 12 ? 'morning' : 'evening';
    }

    private function formatClock(Carbon $time): string
    {
        $hour24 = (int) $time->hour;
        $minute = $time->format('i');
        $hour12 = $hour24 % 12;
        if ($hour12 === 0) {
            $hour12 = 12;
        }

        return sprintf('%d:%s', $hour12, $minute);
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
