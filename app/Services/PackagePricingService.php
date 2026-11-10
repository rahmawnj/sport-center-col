<?php

namespace App\\Services;

use App\\Models\\Package;
use App\\Models\\PackagePricingRule;
use Illuminate\\Support\\Carbon;
use Illuminate\\Support\\Collection;

class PackagePricingService
{
    /**
     * Ambil semua aturan harga yang berlaku pada tanggal tertentu.
     * Prioritas tertinggi menang; jika sama, rentang waktu paling spesifik menang.
     *
     * @return Collection<int, PackagePricingRule>
     */
    public function rulesForDate(Package $package, Carbon $date): Collection
    {
        $dayType = $date->isWeekend() ? 'weekend' : 'weekday';
        $dateString = $date->toDateString();

        return $package->pricingRules
            ->filter(fn ($rule) => in_array($rule->day_type, [$dayType, 'all'], true)
                && $this->isActiveOn($rule, $dateString))
            ->sortBy([
                fn ($a, $b) => $b->priority <=> $a->priority,
                fn ($a, $b) => $this->windowWidth($a) <=> $this->windowWidth($b),
            ])
            ->values();
    }

    /**
     * Ringkasan harga untuk ditampilkan pada katalog/halaman mana pun.
     *
     * @return array{starting_price: ?float, day_type: string, rules: array<int, array<string, mixed>>}
     */
    public function summary(Package $package, Carbon $date): array
    {
        if ($package->type === 'membership') {
            return [
                'starting_price' => (float) $package->price,
                'day_type' => $date->isWeekend() ? 'weekend' : 'weekday',
                'rules' => [],
            ];
        }

        $rules = $this->rulesForDate($package, $date);

        return [
            'starting_price' => $rules->isEmpty() ? null : (float) $rules->min('price'),
            'day_type' => $date->isWeekend() ? 'weekend' : 'weekday',
            'rules' => $rules->map(fn ($rule) => [
                'day_type' => $rule->day_type,
                'start_time' => $rule->start_time ? substr((string) $rule->start_time, 0, 5) : null,
                'end_time' => $rule->end_time ? substr((string) $rule->end_time, 0, 5) : null,
                'price' => (float) $rule->price,
                'priority' => (int) $rule->priority,
            ])->values()->all(),
        ];
    }

    /**
     * Hitung harga final berdasarkan tanggal, jam mulai, durasi, dan penyesuaian.
     * Hasil ini bisa dipakai ulang oleh booking, checkout, receipt, atau halaman lain.
     *
     * @return array{available: bool, date: string, day_type: string, start_time: ?string, duration_hours: int, breakdown: array<int, array{time: string, price: float}>, subtotal: ?float, adjustment: float, total: ?float}
     */
    public function quote(
        Package $package,
        Carbon $date,
        ?string $startTime = null,
        int $durationHours = 1,
        float $adjustment = 0,
    ): array {
        $durationHours = max(1, $durationHours);
        $dayType = $date->isWeekend() ? 'weekend' : 'weekday';
        $rules = $this->rulesForDate($package, $date);
        $breakdown = [];

        if ($startTime === null || $startTime === '') {
            $base = $package->type === 'membership'
                ? (float) $package->price
                : ($rules->isEmpty() ? null : (float) $rules->min('price'));

            return [
                'available' => $base !== null,
                'date' => $date->toDateString(),
                'day_type' => $dayType,
                'start_time' => null,
                'duration_hours' => $durationHours,
                'breakdown' => [],
                'subtotal' => $base,
                'adjustment' => $adjustment,
                'total' => $base === null ? null : $base + $adjustment,
            ];
        }

        $start = $this->toMinutes($startTime);
        for ($minute = $start; $minute < $start + ($durationHours * 60); $minute += 60) {
            $price = $this->priceAt($rules, $minute);
            if ($price === null) {
                return [
                    'available' => false,
                    'date' => $date->toDateString(),
                    'day_type' => $dayType,
                    'start_time' => $startTime,
                    'duration_hours' => $durationHours,
                    'breakdown' => $breakdown,
                    'subtotal' => null,
                    'adjustment' => $adjustment,
                    'total' => null,
                ];
            }

            $breakdown[] = [
                'time' => $this->toTime($minute),
                'price' => $price,
            ];
        }

        $subtotal = array_sum(array_column($breakdown, 'price'));

        return [
            'available' => true,
            'date' => $date->toDateString(),
            'day_type' => $dayType,
            'start_time' => $startTime,
            'duration_hours' => $durationHours,
            'breakdown' => $breakdown,
            'subtotal' => $subtotal,
            'adjustment' => $adjustment,
            'total' => $subtotal + $adjustment,
        ];
    }

    public function priceAt(Collection $rules, int $startMinutes): ?float
    {
        $rule = $rules->first(function ($rule) use ($startMinutes) {
            $ruleStart = $rule->start_time ? $this->toMinutes((string) $rule->start_time) : 0;
            $ruleEnd = $rule->end_time ? $this->toMinutes((string) $rule->end_time) : 1440;

            return $startMinutes >= $ruleStart && $startMinutes < $ruleEnd;
        });

        return $rule ? (float) $rule->price : null;
    }

    public function isActiveOn($rule, string $dateString): bool
    {
        return ($rule->date_start === null || $rule->date_start->toDateString() <= $dateString)
            && ($rule->date_end === null || $rule->date_end->toDateString() >= $dateString);
    }

    private function windowWidth($rule): int
    {
        $start = $rule->start_time ? $this->toMinutes((string) $rule->start_time) : 0;
        $end = $rule->end_time ? $this->toMinutes((string) $rule->end_time) : 1440;

        return $end - $start;
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hours * 60) + (int) $minutes;
    }

    private function toTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60) % 24, $minutes % 60);
    }
}
