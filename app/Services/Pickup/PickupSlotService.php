<?php

namespace App\Services\Pickup;

use App\Models\PickupSlotWindow;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PickupSlotService
{
    public function calendar(int $days = 5, int $leadMinutes = 45): array
    {
        if (! Schema::hasTable('pickup_slot_windows')) {
            return [
                'now_available' => true,
                'now_label' => 'الآن',
                'days' => [],
            ];
        }

        $windows = PickupSlotWindow::query()
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
                'weekday_label' => \App\Models\DeliverySlotWindow::weekdayNames()[$weekday] ?? '',
                'slots' => $slots,
            ];
        }

        return [
            'now_available' => true,
            'now_label' => 'الآن',
            'days' => $items,
        ];
    }

    public function isValidScheduledAt(Carbon|string $scheduledAt): bool
    {
        $time = $scheduledAt instanceof Carbon ? $scheduledAt : Carbon::parse($scheduledAt);
        $calendar = $this->calendar(days: 14, leadMinutes: 45);

        foreach ($calendar['days'] as $day) {
            foreach ($day['slots'] as $slot) {
                if (! ($slot['available'] ?? false)) {
                    continue;
                }
                $slotTime = Carbon::parse($slot['starts_at']);
                if ($slotTime->equalTo($time)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, PickupSlotWindow>  $windows
     * @return list<array<string, mixed>>
     */
    private function slotsForDate(Carbon $date, Collection $windows, Carbon $now, int $leadMinutes): array
    {
        $slots = [];

        foreach ($windows as $window) {
            $interval = max(5, min(60, (int) $window->interval_minutes));
            $start = Carbon::parse($date->toDateString().' '.$window->start_time);
            $end = Carbon::parse($date->toDateString().' '.$window->end_time);
            $cursor = $start->copy();

            while ($cursor->lessThan($end)) {
                $available = $cursor->greaterThan($now->copy()->addMinutes($leadMinutes));
                $hour24 = (int) $cursor->hour;
                $minute = (int) $cursor->minute;
                $period = $this->periodForHour($hour24);
                $suffix = $period === 'morning' ? 'ص' : 'م';
                $hour12 = $hour24 % 12;
                if ($hour12 === 0) {
                    $hour12 = 12;
                }
                $timeLabel = sprintf('%d:%02d %s', $hour12, $minute, $suffix);
                $startLabel = $cursor->format('H:i');

                $slots[] = [
                    'id' => $date->toDateString().'_'.$startLabel,
                    'hour' => $hour12,
                    'minute' => $minute,
                    'hour_24' => $hour24,
                    'period' => $period,
                    'period_letter' => $suffix,
                    'time_label' => $timeLabel,
                    'start' => $startLabel,
                    'end' => $startLabel,
                    'label' => $timeLabel,
                    'available' => $available,
                    'starts_at' => $cursor->toIso8601String(),
                ];

                $cursor->addMinutes($interval);
            }
        }

        usort($slots, static function (array $a, array $b): int {
            return strcmp($a['start'], $b['start']);
        });

        return $slots;
    }

    private function periodForHour(int $hour): string
    {
        return $hour < 12 ? 'morning' : 'evening';
    }

    private function dayLabel(Carbon $date, int $offset): string
    {
        return match ($offset) {
            0 => 'اليوم',
            1 => 'غداً',
            default => \App\Models\DeliverySlotWindow::weekdayNames()[(int) $date->dayOfWeek] ?? $date->translatedFormat('l'),
        };
    }
}
