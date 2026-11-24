<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { BadgeCheck, CalendarClock, CircleDollarSign, Receipt } from '@lucide/vue';
import { computed } from 'vue';
import SummaryCards from '@/components/SummaryCards.vue';

type Stats = {
    revenue_today: number;
    transactions_today: number;
    bookings_today: number;
    classes_today: number;
    active_members: number;
};
type RevenuePoint = { label: string; total: number };
type BookingStatus = { pending: number; confirmed: number; completed: number; cancelled: number };

const props = defineProps<{ stats: Stats; revenue: RevenuePoint[]; bookingStatus: BookingStatus }>();

const formatIDR = (value: number) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(value);

const cards = computed(() => [
    { label: 'Pendapatan hari ini', value: formatIDR(props.stats.revenue_today), icon: CircleDollarSign, accent: 'emerald' as const },
    { label: 'Transaksi hari ini', value: props.stats.transactions_today, icon: Receipt, accent: 'sky' as const },
    { label: 'Booking hari ini', value: props.stats.bookings_today, icon: CalendarClock, accent: 'violet' as const },
    { label: 'Kelas hari ini', value: props.stats.classes_today, icon: CalendarClock, accent: 'amber' as const },
    { label: 'Member aktif', value: props.stats.active_members, icon: BadgeCheck, accent: 'emerald' as const },
]);

const maxRevenue = computed(() => Math.max(1, ...props.revenue.map(item => item.total)));
const statusItems = computed(() => [
    { key: 'pending', label: 'Menunggu', color: 'bg-amber-500', value: props.bookingStatus.pending },
    { key: 'confirmed', label: 'Terkonfirmasi', color: 'bg-emerald-500', value: props.bookingStatus.confirmed },
    { key: 'completed', label: 'Selesai', color: 'bg-sky-500', value: props.bookingStatus.completed },
    { key: 'cancelled', label: 'Dibatalkan', color: 'bg-rose-500', value: props.bookingStatus.cancelled },
]);
</script>

<template>
    <Head title="Dashboard Statistik" />
    <div class="space-y-6 p-4 md:p-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-primary">
                <span class="size-2 rounded-full bg-primary"></span> Ringkasan bisnis
            </div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">Dashboard Statistik</h1>
            <p class="mt-1 text-sm text-muted-foreground">Pantau performa dan pencapaian Sport Center berdasarkan data transaksi dan aktivitas.</p>
        </div>

        <SummaryCards :items="cards" />

        <div class="grid gap-4 xl:grid-cols-3">
            <section class="rounded-2xl border bg-card p-5 shadow-sm xl:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">Pendapatan 7 hari terakhir</h2>
                        <p class="text-xs text-muted-foreground">Akumulasi transaksi yang sudah lunas</p>
                    </div>
                    <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600">7 hari</span>
                </div>
                <div class="mt-6 flex h-56 items-end gap-3 border-b px-1">
                    <div v-for="item in revenue" :key="item.label" class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2">
                        <span class="max-w-full truncate text-[10px] text-muted-foreground">{{ item.total ? formatIDR(item.total) : '—' }}</span>
                        <div class="flex h-36 w-full items-end overflow-hidden rounded-t-lg bg-muted">
                            <div class="w-full rounded-t-lg bg-emerald-500 transition-all" :style="{ height: Math.max(item.total ? 5 : 0, item.total / maxRevenue * 100) + '%' }"></div>
                        </div>
                        <span class="pb-2 text-xs text-muted-foreground">{{ item.label }}</span>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <h2 class="font-semibold">Status booking hari ini</h2>
                <p class="text-xs text-muted-foreground">Perbandingan status pemesanan</p>
                <div class="mt-6 space-y-5">
                    <div v-for="item in statusItems" :key="item.key">
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">{{ item.label }}</span>
                            <strong>{{ item.value }}</strong>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div class="h-full rounded-full" :class="item.color" :style="{ width: (item.value / Math.max(1, ...statusItems.map(x => x.value)) * 100) + '%' }"></div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
