<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    BadgeCheck,
    CalendarClock,
    CircleDollarSign,
    Clock,
    LayoutGrid,
    MapPin,
    Receipt,
} from '@lucide/vue';
import { computed } from 'vue';
import SummaryCards from '@/components/SummaryCards.vue';

type Stats = {
    revenue_today: number;
    transactions_today: number;
    bookings_today: number;
    active_members: number;
    courts_in_use: number;
    courts_total: number;
};
type RevenuePoint = { label: string; total: number };
type BookingStatus = {
    pending: number;
    confirmed: number;
    completed: number;
    cancelled: number;
};
type CourtMonitor = {
    id: number;
    name: string;
    facility: string | null;
    status: string;
    in_use: boolean;
    current: {
        customer: string;
        start_time: string;
        end_time: string;
    } | null;
};
type BookingRow = {
    id: number;
    court: string | null;
    customer: string;
    start_time: string;
    end_time: string;
    status: string;
};

const props = defineProps<{
    stats: Stats;
    revenue: RevenuePoint[];
    bookingStatus: BookingStatus;
    courtMonitor: CourtMonitor[];
    bookings: BookingRow[];
}>();

const statCards = computed(() => [
    {
        label: 'Pendapatan hari ini',
        value: formatIDR(props.stats.revenue_today),
        icon: CircleDollarSign,
        accent: 'emerald' as const,
    },
    {
        label: 'Transaksi hari ini',
        value: props.stats.transactions_today,
        icon: Receipt,
        accent: 'sky' as const,
    },
    {
        label: 'Booking hari ini',
        value: props.stats.bookings_today,
        icon: CalendarClock,
        accent: 'violet' as const,
    },
    {
        label: 'Member aktif',
        value: props.stats.active_members,
        icon: BadgeCheck,
        accent: 'amber' as const,
    },
]);

// --- Revenue area chart ---
const W = 640;
const H = 220;
const PAD = 28;

const maxRevenue = computed(() =>
    Math.max(1, ...props.revenue.map((point) => point.total)),
);

const chartPoints = computed(() => {
    const count = props.revenue.length;

    return props.revenue.map((point, index) => {
        const x =
            count <= 1 ? W / 2 : PAD + (index * (W - 2 * PAD)) / (count - 1);
        const y = H - PAD - (point.total / maxRevenue.value) * (H - 2 * PAD);

        return { x, y, ...point };
    });
});

const linePath = computed(() =>
    chartPoints.value
        .map(
            (point, index) => `${index === 0 ? 'M' : 'L'}${point.x},${point.y}`,
        )
        .join(' '),
);

const areaPath = computed(() => {
    const points = chartPoints.value;

    if (!points.length) {
        return '';
    }

    const first = points[0];
    const last = points[points.length - 1];

    return `${linePath.value} L${last.x},${H - PAD} L${first.x},${H - PAD} Z`;
});

const gridLines = [0, 1, 2, 3, 4].map((i) => H - PAD - (i / 4) * (H - 2 * PAD));

// --- Booking status bars ---
const statusBars = computed(() => {
    const entries = [
        { key: 'pending', label: 'Menunggu', color: 'bg-amber-500' },
        { key: 'confirmed', label: 'Terkonfirmasi', color: 'bg-emerald-500' },
        { key: 'completed', label: 'Selesai', color: 'bg-sky-500' },
        { key: 'cancelled', label: 'Dibatalkan', color: 'bg-rose-500' },
    ] as const;

    const max = Math.max(
        1,
        ...entries.map((entry) => props.bookingStatus[entry.key]),
    );

    return entries.map((entry) => ({
        ...entry,
        value: props.bookingStatus[entry.key],
        height: Math.round((props.bookingStatus[entry.key] / max) * 100),
    }));
});

const bookingStatusMeta: Record<string, { label: string; badge: string }> = {
    pending: {
        label: 'Menunggu',
        badge: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    },
    confirmed: {
        label: 'Terkonfirmasi',
        badge: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    },
    completed: {
        label: 'Selesai',
        badge: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    },
    cancelled: {
        label: 'Dibatalkan',
        badge: 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
    },
};

function formatIDR(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}
</script>

<template>
    <Head title="Dashboard" />
    <div class="space-y-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Ringkasan operasional Sport Center hari ini.
            </p>
        </div>

        <SummaryCards :items="statCards" />

        <!-- Charts -->
        <div class="grid gap-4 lg:grid-cols-3">
            <!-- Revenue -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold">
                            Pendapatan 7 hari terakhir
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Total pembayaran lunas per hari
                        </p>
                    </div>
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                    >
                        <CircleDollarSign class="size-5" />
                    </div>
                </div>

                <div class="mt-4">
                    <svg
                        :viewBox="`0 0 ${W} ${H}`"
                        class="h-56 w-full overflow-visible"
                        preserveAspectRatio="none"
                    >
                        <defs>
                            <linearGradient
                                id="revenueFill"
                                x1="0"
                                y1="0"
                                x2="0"
                                y2="1"
                            >
                                <stop
                                    offset="0%"
                                    stop-color="rgb(16 185 129)"
                                    stop-opacity="0.35"
                                />
                                <stop
                                    offset="100%"
                                    stop-color="rgb(16 185 129)"
                                    stop-opacity="0"
                                />
                            </linearGradient>
                        </defs>

                        <line
                            v-for="(line, index) in gridLines"
                            :key="index"
                            :x1="PAD"
                            :x2="W - PAD"
                            :y1="line"
                            :y2="line"
                            class="stroke-border"
                            stroke-width="1"
                        />

                        <path :d="areaPath" fill="url(#revenueFill)" />
                        <path
                            :d="linePath"
                            fill="none"
                            stroke="rgb(16 185 129)"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                        <g v-for="point in chartPoints" :key="point.label">
                            <circle
                                :cx="point.x"
                                :cy="point.y"
                                r="4"
                                class="fill-card"
                                stroke="rgb(16 185 129)"
                                stroke-width="2.5"
                            />
                            <text
                                :x="point.x"
                                :y="H - 8"
                                text-anchor="middle"
                                class="fill-muted-foreground"
                                font-size="12"
                            >
                                {{ point.label }}
                            </text>
                        </g>
                    </svg>

                    <div
                        class="mt-2 flex justify-between text-xs text-muted-foreground"
                    >
                        <span>Maks {{ formatIDR(maxRevenue) }}</span>
                    </div>
                </div>
            </div>

            <!-- Booking status -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <h2 class="font-semibold">Status booking hari ini</h2>
                <p class="text-xs text-muted-foreground">
                    Distribusi status pemesanan
                </p>

                <div class="mt-4 flex h-44 items-end justify-between gap-3">
                    <div
                        v-for="bar in statusBars"
                        :key="bar.key"
                        class="flex flex-1 flex-col items-center gap-2"
                    >
                        <span class="text-sm font-semibold">{{
                            bar.value
                        }}</span>
                        <div
                            class="flex w-full flex-1 items-end overflow-hidden rounded-lg bg-muted"
                        >
                            <div
                                class="w-full rounded-lg transition-all"
                                :class="bar.color"
                                :style="{
                                    height: `${bar.value === 0 ? 4 : bar.height}%`,
                                }"
                            ></div>
                        </div>
                        <span
                            class="text-center text-[11px] leading-tight text-muted-foreground"
                        >
                            {{ bar.label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monitoring + bookings -->
        <div class="grid gap-4 lg:grid-cols-3">
            <!-- Courts monitoring -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">Monitoring lapangan</h2>
                    <span
                        class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                    >
                        {{ props.stats.courts_in_use }}/{{
                            props.stats.courts_total
                        }}
                        dipakai
                    </span>
                </div>
                <p class="text-xs text-muted-foreground">
                    Status pemakaian lapangan saat ini
                </p>

                <div class="mt-4 space-y-2">
                    <div
                        v-for="court in props.courtMonitor"
                        :key="court.id"
                        class="flex items-center justify-between gap-3 rounded-xl border p-3 transition-colors"
                        :class="
                            court.in_use
                                ? 'border-emerald-500/40 bg-emerald-500/5'
                                : ''
                        "
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                                :class="
                                    court.in_use
                                        ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                        : 'bg-muted text-muted-foreground'
                                "
                            >
                                <MapPin class="size-4" />
                            </div>
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium">
                                    {{ court.name }}
                                </div>
                                <div
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ court.facility || '—' }}
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="court.in_use && court.current"
                            class="shrink-0 text-right"
                        >
                            <div
                                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400"
                            >
                                <span
                                    class="size-1.5 animate-pulse rounded-full bg-emerald-500"
                                />
                                Dipakai
                            </div>
                            <div class="mt-1 text-[11px] text-muted-foreground">
                                {{ court.current.customer }} ·
                                {{ court.current.start_time }}–{{
                                    court.current.end_time
                                }}
                            </div>
                        </div>
                        <div
                            v-else
                            class="shrink-0 rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground"
                        >
                            {{
                                court.status === 'available'
                                    ? 'Kosong'
                                    : court.status
                            }}
                        </div>
                    </div>

                    <p
                        v-if="!props.courtMonitor.length"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        Belum ada lapangan.
                    </p>
                </div>
            </div>

            <!-- Today's bookings -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold">Booking hari ini</h2>
                        <p class="text-xs text-muted-foreground">
                            Daftar pemesanan lapangan hari ini
                        </p>
                    </div>
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400"
                    >
                        <Clock class="size-5" />
                    </div>
                </div>

                <div class="mt-4 overflow-hidden rounded-xl border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-left">
                            <tr>
                                <th class="px-4 py-2.5 font-medium">Jam</th>
                                <th class="px-4 py-2.5 font-medium">
                                    Lapangan
                                </th>
                                <th class="px-4 py-2.5 font-medium">
                                    Pelanggan
                                </th>
                                <th class="px-4 py-2.5 text-right font-medium">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="booking in props.bookings"
                                :key="booking.id"
                                class="hover:bg-muted/30"
                            >
                                <td class="px-4 py-2.5 font-medium">
                                    {{ booking.start_time }}–{{
                                        booking.end_time
                                    }}
                                </td>
                                <td class="px-4 py-2.5">{{ booking.court }}</td>
                                <td class="px-4 py-2.5">
                                    {{ booking.customer }}
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="
                                            bookingStatusMeta[booking.status]
                                                ?.badge ??
                                            'bg-muted text-muted-foreground'
                                        "
                                    >
                                        {{
                                            bookingStatusMeta[booking.status]
                                                ?.label ?? booking.status
                                        }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!props.bookings.length">
                                <td
                                    colspan="4"
                                    class="px-4 py-10 text-center text-muted-foreground"
                                >
                                    <LayoutGrid
                                        class="mx-auto size-8 text-muted-foreground/50"
                                    />
                                    <p class="mt-2 text-sm">
                                        Belum ada booking hari ini.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
