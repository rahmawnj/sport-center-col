<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CalendarClock, Clock3, MapPin, RefreshCw } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

type Court = {
    id: number; name: string; facility: string | null; status: string;
    in_use: boolean; current: { customer: string; start_time: string; end_time: string } | null;
};
type Booking = { id: number; court: string | null; customer: string; start_time: string; end_time: string; status: string };
type ClassItem = {
    id: number; class_name: string; facility: string | null; trainer: string | null;
    customer: string; start_time: string; end_time: string; status: string;
    payment_status: string | null; invoice_number: string | null;
};
const props = defineProps<{
    generatedAt: string;
    selectedDate: string;
    isToday: boolean;
    courtMonitor: Court[];
    bookings: Booking[];
    todaysClasses: ClassItem[];
}>();

const refreshing = ref(false);
const selectedDate = ref(props.selectedDate);
let poller: ReturnType<typeof setInterval> | undefined;
const usedCourts = computed(() => props.courtMonitor.filter(item => item.in_use).length);
const paymentLabel = (status: string | null) => ({
    fully_paid: 'Lunas', dp_paid: 'DP dibayar', unpaid: 'Belum dibayar',
}[status ?? ''] ?? status ?? '—');
const statusLabel = (status: string) => ({
    pending: 'Menunggu', confirmed: 'Terkonfirmasi', completed: 'Selesai',
    cancelled: 'Dibatalkan', attended: 'Hadir',
}[status] ?? status);
const statusClass = (status: string) => ({
    pending: 'bg-amber-500/10 text-amber-600',
    confirmed: 'bg-emerald-500/10 text-emerald-600',
    completed: 'bg-sky-500/10 text-sky-600',
    cancelled: 'bg-rose-500/10 text-rose-600',
    fully_paid: 'bg-emerald-500/10 text-emerald-600',
    unpaid: 'bg-amber-500/10 text-amber-600',
    dp_paid: 'bg-sky-500/10 text-sky-600',
}[status] ?? 'bg-muted text-muted-foreground');

function refresh() {
    if (refreshing.value) return;
    refreshing.value = true;
    router.reload({
        only: ['generatedAt', 'selectedDate', 'isToday', 'courtMonitor', 'bookings', 'todaysClasses'],
        onFinish: () => { refreshing.value = false; },
    });
}

watch(selectedDate, (date) => {
    router.get('/monitoring', { date }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});

onMounted(() => { poller = setInterval(refresh, 10000); });
onUnmounted(() => { if (poller) clearInterval(poller); });
</script>

<template>
    <Head title="Monitoring" />
    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-600">
                    <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-500 opacity-50"></span><span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span></span>
                    Live operational monitor
                </div>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight">Monitoring</h1>
                <p class="mt-1 text-sm text-muted-foreground">Pantau aktivitas lapangan, booking, kelas, dan transaksi berdasarkan tanggal.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <label class="flex items-center gap-2 rounded-xl border bg-card px-3 py-2 text-sm">
                    <CalendarClock class="size-4 text-muted-foreground" />
                    <span class="text-muted-foreground">Tanggal</span>
                    <input v-model="selectedDate" type="date" class="bg-transparent font-medium outline-none" />
                </label>
                <div class="flex items-center gap-3 rounded-xl border bg-card px-3 py-2">
                <div class="text-right">
                    <p class="text-[11px] text-muted-foreground">Pembaruan terakhir</p>
                    <p class="font-mono text-sm font-semibold">{{ generatedAt }}</p>
                </div>
                <button type="button" @click="refresh" class="inline-flex size-9 items-center justify-center rounded-lg border hover:bg-muted" :disabled="refreshing" title="Perbarui sekarang">
                    <RefreshCw class="size-4" :class="{ 'animate-spin': refreshing }" />
                </button>
                </div>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border bg-card p-4">
                <div class="flex items-center justify-between text-sm text-muted-foreground"><span>Lapangan digunakan</span><MapPin class="size-4" /></div>
                <p class="mt-3 text-3xl font-semibold tabular-nums">{{ usedCourts }}<span class="text-base font-normal text-muted-foreground"> / {{ courtMonitor.length }}</span></p>
                <p class="mt-1 text-xs text-muted-foreground">{{ isToday ? 'Kondisi berdasarkan jam sekarang' : 'Status real-time hanya tersedia untuk hari ini' }}</p>
            </div>
            <div class="rounded-2xl border bg-card p-4">
                <div class="flex items-center justify-between text-sm text-muted-foreground"><span>Booking tanggal terpilih</span><Clock3 class="size-4" /></div>
                <p class="mt-3 text-3xl font-semibold tabular-nums">{{ bookings.length }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Pemesanan yang belum dibatalkan</p>
            </div>
            <div class="rounded-2xl border bg-card p-4">
                <div class="flex items-center justify-between text-sm text-muted-foreground"><span>Peserta kelas tanggal terpilih</span><CalendarClock class="size-4" /></div>
                <p class="mt-3 text-3xl font-semibold tabular-nums">{{ todaysClasses.length }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Peserta pada jadwal hari ini</p>
            </div>
        </div>

        <section class="rounded-2xl border bg-card p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div><h2 class="font-semibold">{{ isToday ? 'Status lapangan saat ini' : 'Daftar lapangan' }}</h2><p class="text-xs text-muted-foreground">{{ isToday ? 'Lapangan yang sedang dipakai akan ditandai aktif.' : 'Riwayat booking ditampilkan untuk tanggal yang dipilih; status pemakaian real-time tidak berlaku untuk tanggal lampau.' }}</p></div>
                <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600">{{ usedCourts }} sedang digunakan</span>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <article v-for="court in courtMonitor" :key="court.id" class="rounded-xl border p-4" :class="court.in_use ? 'border-emerald-500/40 bg-emerald-500/[0.04]' : ''">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0"><p class="truncate font-semibold">{{ court.name }}</p><p class="mt-1 truncate text-xs text-muted-foreground">{{ court.facility || 'Fasilitas tidak diketahui' }}</p></div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold" :class="court.in_use ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground'">{{ court.in_use ? 'Sedang dipakai' : (court.status === 'available' ? 'Kosong' : court.status) }}</span>
                    </div>
                    <div v-if="court.current" class="mt-4 border-t pt-3">
                        <p class="text-sm font-medium">{{ court.current.customer }}</p>
                        <p class="mt-1 font-mono text-xs text-muted-foreground">{{ court.current.start_time }} – {{ court.current.end_time }}</p>
                    </div>
                </article>
                <p v-if="!courtMonitor.length" class="col-span-full py-8 text-center text-sm text-muted-foreground">Belum ada data lapangan.</p>
            </div>
        </section>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="mb-4"><h2 class="font-semibold">Booking tanggal terpilih</h2><p class="text-xs text-muted-foreground">Daftar pemesanan pada tanggal yang dipilih.</p></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b text-left text-muted-foreground"><tr><th class="pb-3 pr-3 font-medium">Waktu</th><th class="pb-3 pr-3 font-medium">Lapangan</th><th class="pb-3 pr-3 font-medium">Pelanggan</th><th class="pb-3 font-medium">Status</th></tr></thead>
                        <tbody class="divide-y">
                            <tr v-for="booking in bookings" :key="booking.id"><td class="whitespace-nowrap py-3 pr-3 font-mono text-xs">{{ booking.start_time }}–{{ booking.end_time }}</td><td class="py-3 pr-3">{{ booking.court || '—' }}</td><td class="py-3 pr-3">{{ booking.customer }}</td><td class="py-3"><span class="rounded-full px-2 py-1 text-xs" :class="statusClass(booking.status)">{{ statusLabel(booking.status) }}</span></td></tr>
                            <tr v-if="!bookings.length"><td colspan="4" class="py-8 text-center text-muted-foreground">Tidak ada booking pada tanggal ini.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="mb-4"><h2 class="font-semibold">Kelas tanggal terpilih</h2><p class="text-xs text-muted-foreground">Peserta dan jadwal kelas pada tanggal yang dipilih.</p></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b text-left text-muted-foreground"><tr><th class="pb-3 pr-3 font-medium">Waktu</th><th class="pb-3 pr-3 font-medium">Kelas / peserta</th><th class="pb-3 font-medium">Status</th></tr></thead>
                        <tbody class="divide-y">
                            <tr v-for="item in todaysClasses" :key="item.id"><td class="whitespace-nowrap py-3 pr-3 font-mono text-xs">{{ item.start_time }}–{{ item.end_time }}</td><td class="py-3 pr-3"><p class="font-medium">{{ item.class_name }} · {{ item.customer }}</p><p class="text-xs text-muted-foreground">{{ item.facility || '—' }} · {{ item.trainer || 'Trainer belum ditentukan' }}</p></td><td class="py-3"><span class="rounded-full px-2 py-1 text-xs" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span></td></tr>
                            <tr v-if="!todaysClasses.length"><td colspan="3" class="py-8 text-center text-muted-foreground">Tidak ada peserta kelas pada tanggal ini.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <p class="text-center text-xs text-muted-foreground">Data diperbarui otomatis setiap 10 detik untuk tanggal yang dipilih. {{ isToday ? 'Status lapangan menunjukkan kondisi real-time.' : 'Untuk tanggal selain hari ini, status lapangan real-time tidak ditampilkan.' }}</p>
    </div>
</template>
