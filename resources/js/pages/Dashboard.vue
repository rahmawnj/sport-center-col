<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Activity, CalendarRange, CheckCircle2, CircleDollarSign, Clock3, CreditCard, Receipt, RotateCcw, Search, TrendingUp } from '@lucide/vue';
import { computed, ref } from 'vue';

type Summary = { transaction_count: number; revenue: number; average_transaction: number };
type CategoryRow = { type: string; label: string; transaction_count: number; revenue: number };
type DailyRow = { date: string; transaction_count: number; revenue: number };
type PaymentMethod = { method: string; label: string; transaction_count: number; revenue: number };
type TransactionRow = {
    id: number; invoice_number: string | null; customer: string; type: string | null;
    type_label: string; payment_method: string | null; amount: number; created_at: string | null;
};

const props = defineProps<{
    summary: Summary;
    categoryRows: CategoryRow[];
    dailyRows: DailyRow[];
    paymentMethods: PaymentMethod[];
    transactions: TransactionRow[];
    filters: { from: string | null; to: string | null };
}>();

const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const rangeOpen = ref(false);
const rangeStart = ref(from.value);
const rangeEnd = ref(to.value);
const datePresets = [
    { value: 'today', label: 'Hari ini' },
    { value: '7days', label: '7 hari terakhir' },
    { value: '30days', label: '30 hari terakhir' },
    { value: 'month', label: 'Bulan ini' },
] as const;

function parseDate(value: string) {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
}
function toISO(date: Date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}
function monthStart(value: string | Date) {
    const date = typeof value === 'string' ? parseDate(value) : value;
    return new Date(date.getFullYear(), date.getMonth(), 1);
}
const viewMonth = ref(monthStart(from.value || to.value || new Date()));
const monthViews = computed(() => [0, 1].map((offset) => {
    const month = new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth() + offset, 1);
    const firstDay = new Date(month.getFullYear(), month.getMonth(), 1);
    const mondayOffset = (firstDay.getDay() + 6) % 7;
    const gridStart = new Date(firstDay);
    gridStart.setDate(firstDay.getDate() - mondayOffset);
    const days = Array.from({ length: 42 }, (_, index) => {
        const date = new Date(gridStart);
        date.setDate(gridStart.getDate() + index);
        return { value: toISO(date), day: date.getDate(), inMonth: date.getMonth() === month.getMonth() };
    });
    return {
        key: `${month.getFullYear()}-${month.getMonth()}`,
        title: new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(month),
        days,
    };
}));
const rangeLabel = computed(() => {
    if (!from.value && !to.value) return 'Semua riwayat';
    return `${formatDate(from.value)} – ${formatDate(to.value)}`;
});
function formatDate(value: string) {
    if (!value) return 'Pilih tanggal';
    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(parseDate(value));
}
function shiftMonths(amount: number) {
    viewMonth.value = new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth() + amount, 1);
}
function isInRange(value: string) {
    return !!rangeStart.value && !!rangeEnd.value && value >= rangeStart.value && value <= rangeEnd.value;
}
function isRangeEdge(value: string) {
    return value === rangeStart.value || value === rangeEnd.value;
}
function applyFilter() {
    router.get('/dashboard', { from: from.value || undefined, to: to.value || undefined }, {
        preserveState: true, preserveScroll: true, replace: true,
    });
}
function chooseDay(value: string) {
    if (!rangeStart.value || rangeEnd.value) {
        rangeStart.value = value;
        rangeEnd.value = '';
        return;
    }
    if (value < rangeStart.value) {
        rangeStart.value = value;
        rangeEnd.value = '';
        return;
    }
    rangeEnd.value = value;
    from.value = rangeStart.value;
    to.value = rangeEnd.value;
    rangeOpen.value = false;
    applyFilter();
}
function applyPreset(preset: 'today' | '7days' | '30days' | 'month') {
    const end = new Date();
    const start = new Date(end);
    if (preset === '7days') start.setDate(end.getDate() - 6);
    if (preset === '30days') start.setDate(end.getDate() - 29);
    if (preset === 'month') start.setDate(1);
    from.value = toISO(start);
    to.value = toISO(end);
    rangeStart.value = from.value;
    rangeEnd.value = to.value;
    viewMonth.value = monthStart(from.value);
    rangeOpen.value = false;
    applyFilter();
}
function resetFilter() {
    from.value = '';
    to.value = '';
    rangeStart.value = '';
    rangeEnd.value = '';
    rangeOpen.value = false;
    router.get('/dashboard', {}, { preserveState: true, preserveScroll: true, replace: true });
}

const formatIDR = (value: number) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(value);
const formatCompactIDR = (value: number) => new Intl.NumberFormat('id-ID', {
    notation: 'compact', maximumFractionDigits: 1,
}).format(value);
const formatDateTime = (value: string | null) => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    }).format(new Date(value.replace(' ', 'T')));
};
const methodLabel = (value: string | null) => ({
    cash: 'Tunai', bank_transfer: 'Transfer bank', qris: 'QRIS',
}[value ?? ''] ?? value ?? '—');

const maxRevenue = computed(() => Math.max(1, ...props.dailyRows.map(row => row.revenue)));
const maxCount = computed(() => Math.max(1, ...props.dailyRows.map(row => row.transaction_count)));
const maxCategoryRevenue = computed(() => Math.max(1, ...props.categoryRows.map(row => row.revenue)));
const totalCategoryCount = computed(() => props.categoryRows.reduce((sum, row) => sum + row.transaction_count, 0));
const totalMethodRevenue = computed(() => props.paymentMethods.reduce((sum, row) => sum + row.revenue, 0));
const periodLabel = computed(() => from.value || to.value ? `${formatDate(from.value)} – ${formatDate(to.value)}` : 'Seluruh periode');
</script>

<template>
    <Head title="Dashboard" />
    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-primary">
                    <span class="size-2 rounded-full bg-primary"></span> Laporan penjualan
                </div>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight">Dashboard</h1>
                <p class="mt-1 text-sm text-muted-foreground">Analisis pemasukan, transaksi, dan performa setiap layanan.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1.5 text-xs font-medium text-emerald-600">
                <CheckCircle2 class="size-3.5" /> Hanya transaksi lunas & terverifikasi
            </span>
        </div>

        <section class="rounded-2xl border bg-card p-4 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2"><CalendarRange class="size-4 text-muted-foreground" /><h2 class="text-sm font-semibold">Rentang laporan</h2></div>
                    <p class="mt-1 text-xs text-muted-foreground">Periode: {{ periodLabel }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <button type="button" class="inline-flex h-10 items-center gap-2 rounded-lg border bg-background px-3 text-sm hover:bg-muted" :aria-expanded="rangeOpen" @click="rangeOpen = !rangeOpen">
                            <CalendarRange class="size-4" /> {{ rangeLabel }}
                        </button>
                        <div v-if="rangeOpen" class="absolute right-0 z-30 mt-2 w-[min(680px,calc(100vw-2.5rem))] rounded-xl border bg-popover p-4 text-popover-foreground shadow-xl">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <p class="text-sm font-medium">{{ rangeLabel }}</p>
                                <button type="button" class="text-xs font-medium text-primary hover:underline" @click="resetFilter">Hapus rentang</button>
                            </div>
                            <div class="mb-4 flex flex-wrap gap-2">
                                <button v-for="preset in datePresets" :key="preset.value" type="button" class="rounded-md border px-3 py-1.5 text-xs hover:bg-muted" @click="applyPreset(preset.value)">{{ preset.label }}</button>
                            </div>
                            <div class="mb-3 flex items-center justify-between">
                                <button type="button" class="rounded-md border px-2.5 py-1 text-sm hover:bg-muted" aria-label="Bulan sebelumnya" @click="shiftMonths(-1)">‹</button>
                                <p class="text-xs text-muted-foreground">Pilih tanggal awal, lalu tanggal akhir</p>
                                <button type="button" class="rounded-md border px-2.5 py-1 text-sm hover:bg-muted" aria-label="Bulan berikutnya" @click="shiftMonths(1)">›</button>
                            </div>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div v-for="month in monthViews" :key="month.key" class="min-w-0">
                                    <p class="mb-3 text-center text-sm font-semibold capitalize">{{ month.title }}</p>
                                    <div class="mb-1 grid grid-cols-7 text-center text-xs text-muted-foreground">
                                        <span v-for="day in ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']" :key="day" class="py-1">{{ day }}</span>
                                    </div>
                                    <div class="grid grid-cols-7 gap-y-1">
                                        <button v-for="day in month.days" :key="day.value" type="button" class="mx-auto flex size-8 items-center justify-center rounded-full text-xs transition-colors" :class="[!day.inMonth ? 'text-muted-foreground/40' : 'hover:bg-muted', isInRange(day.value) ? 'rounded-none bg-primary/10 text-primary' : '', isRangeEdge(day.value) ? 'rounded-full bg-primary text-primary-foreground hover:bg-primary' : '']" @click="chooseDay(day.value)">{{ day.day }}</button>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 border-t pt-3 text-xs text-muted-foreground">{{ rangeStart ? formatDate(rangeStart) : 'Tanggal awal' }} – {{ rangeEnd ? formatDate(rangeEnd) : 'Tanggal akhir' }}</div>
                        </div>
                    </div>
                    <button type="button" class="inline-flex size-10 items-center justify-center rounded-lg border hover:bg-muted" title="Reset filter" @click="resetFilter"><RotateCcw class="size-4" /></button>
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border bg-card p-4 shadow-sm">
                <div class="flex items-center justify-between text-sm text-muted-foreground"><span>Total pemasukan</span><CircleDollarSign class="size-4 text-emerald-600" /></div>
                <p class="mt-3 text-2xl font-semibold tabular-nums">{{ formatIDR(summary.revenue) }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Dari transaksi yang sudah lunas</p>
            </article>
            <article class="rounded-2xl border bg-card p-4 shadow-sm">
                <div class="flex items-center justify-between text-sm text-muted-foreground"><span>Jumlah transaksi</span><Receipt class="size-4 text-sky-600" /></div>
                <p class="mt-3 text-2xl font-semibold tabular-nums">{{ summary.transaction_count.toLocaleString('id-ID') }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Transaksi berhasil terverifikasi</p>
            </article>
            <article class="rounded-2xl border bg-card p-4 shadow-sm">
                <div class="flex items-center justify-between text-sm text-muted-foreground"><span>Rata-rata transaksi</span><TrendingUp class="size-4 text-violet-600" /></div>
                <p class="mt-3 text-2xl font-semibold tabular-nums">{{ formatIDR(summary.average_transaction) }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Rata-rata nilai per transaksi</p>
            </article>
            <article class="rounded-2xl border bg-card p-4 shadow-sm">
                <div class="flex items-center justify-between text-sm text-muted-foreground"><span>Kategori terjual</span><Activity class="size-4 text-amber-600" /></div>
                <p class="mt-3 text-2xl font-semibold tabular-nums">{{ categoryRows.filter(row => row.transaction_count > 0).length }} <span class="text-sm font-normal text-muted-foreground">/ 4 kategori</span></p>
                <p class="mt-1 text-xs text-muted-foreground">{{ totalCategoryCount.toLocaleString('id-ID') }} transaksi dari semua layanan</p>
            </article>
        </section>

        <section class="rounded-2xl border bg-card p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><h2 class="font-semibold">Grafik pemasukan harian</h2><p class="text-xs text-muted-foreground">Pergerakan pemasukan dan jumlah transaksi lunas · maksimal 31 tanggal terbaru pada periode ini.</p></div>
                <div class="flex gap-4 text-xs text-muted-foreground"><span class="inline-flex items-center gap-1.5"><i class="size-2.5 rounded-sm bg-primary"></i>Pemasukan</span><span class="inline-flex items-center gap-1.5"><i class="size-2.5 rounded-sm bg-emerald-500"></i>Jumlah transaksi</span></div>
            </div>
            <div v-if="dailyRows.length" class="mt-6 overflow-x-auto">
                <div class="flex min-w-max items-end gap-3 border-b pb-3" style="height: 230px">
                    <div v-for="row in dailyRows" :key="row.date" class="group flex h-full w-12 flex-col items-center justify-end gap-2">
                        <div class="relative flex h-[178px] w-full items-end justify-center gap-1">
                            <div class="w-5 rounded-t-md bg-primary/85 transition-all group-hover:bg-primary" :style="{ height: Math.max(3, row.revenue / maxRevenue * 170) + 'px' }" :title="formatIDR(row.revenue)"></div>
                            <div class="w-2 rounded-t-md bg-emerald-500/80" :style="{ height: Math.max(3, row.transaction_count / maxCount * 170) + 'px' }" :title="row.transaction_count + ' transaksi'"></div>
                        </div>
                        <span class="text-[10px] text-muted-foreground">{{ formatDate(row.date).split(' ').slice(0, 2).join(' ') }}</span>
                    </div>
                </div>
            </div>
            <div v-else class="py-12 text-center text-sm text-muted-foreground"><Activity class="mx-auto mb-2 size-8 opacity-40" />Belum ada pemasukan pada periode ini.</div>
            <div v-if="dailyRows.length" class="mt-4 grid gap-2 sm:grid-cols-2">
                <div class="rounded-xl bg-muted/40 p-3"><p class="text-xs text-muted-foreground">Pemasukan tertinggi per hari</p><p class="mt-1 font-semibold">{{ formatIDR(Math.max(...dailyRows.map(row => row.revenue))) }}</p></div>
                <div class="rounded-xl bg-muted/40 p-3"><p class="text-xs text-muted-foreground">Transaksi terbanyak per hari</p><p class="mt-1 font-semibold">{{ Math.max(...dailyRows.map(row => row.transaction_count)) }} transaksi</p></div>
            </div>
        </section>

        <section class="space-y-3">
            <div><h2 class="font-semibold">Performa per kategori layanan</h2><p class="text-xs text-muted-foreground">Pemasukan dan jumlah transaksi dipisahkan agar tiap layanan bisa dibandingkan.</p></div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="item in categoryRows" :key="item.type" class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-2"><p class="text-sm font-semibold">{{ item.label }}</p><span class="rounded-full bg-muted px-2 py-1 text-[10px] font-medium">{{ item.transaction_count }} transaksi</span></div>
                    <p class="mt-4 text-xl font-semibold tabular-nums">{{ formatIDR(item.revenue) }}</p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-muted"><div class="h-full rounded-full bg-primary" :style="{ width: (item.revenue / maxCategoryRevenue * 100) + '%' }"></div></div>
                    <p class="mt-2 text-xs text-muted-foreground">{{ summary.revenue ? (item.revenue / summary.revenue * 100).toFixed(1) : '0.0' }}% dari total pemasukan</p>
                </article>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center gap-2"><CreditCard class="size-4 text-primary" /><div><h2 class="font-semibold">Pemasukan berdasarkan metode</h2><p class="text-xs text-muted-foreground">Kontribusi metode pembayaran yang sudah lunas.</p></div></div>
                <div v-if="paymentMethods.length" class="mt-5 space-y-4">
                    <div v-for="method in paymentMethods" :key="method.method">
                        <div class="flex items-center justify-between gap-3 text-sm"><span class="font-medium">{{ method.label }}</span><span class="font-semibold tabular-nums">{{ formatIDR(method.revenue) }}</span></div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted"><div class="h-full rounded-full bg-primary" :style="{ width: (totalMethodRevenue ? method.revenue / totalMethodRevenue * 100 : 0) + '%' }"></div></div>
                        <p class="mt-1 text-xs text-muted-foreground">{{ method.transaction_count }} transaksi · {{ totalMethodRevenue ? (method.revenue / totalMethodRevenue * 100).toFixed(1) : '0.0' }}%</p>
                    </div>
                </div>
                <p v-else class="py-8 text-center text-sm text-muted-foreground">Belum ada data metode pembayaran.</p>
            </div>
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center gap-2"><Clock3 class="size-4 text-primary" /><div><h2 class="font-semibold">Ringkasan periode</h2><p class="text-xs text-muted-foreground">Angka utama dari rentang laporan yang dipilih.</p></div></div>
                <div class="mt-5 space-y-3 text-sm">
                    <div class="flex items-center justify-between border-b pb-3"><span class="text-muted-foreground">Periode</span><span class="text-right font-medium">{{ periodLabel }}</span></div>
                    <div class="flex items-center justify-between border-b pb-3"><span class="text-muted-foreground">Total pemasukan</span><span class="font-semibold">{{ formatIDR(summary.revenue) }}</span></div>
                    <div class="flex items-center justify-between border-b pb-3"><span class="text-muted-foreground">Total transaksi berhasil</span><span class="font-semibold">{{ summary.transaction_count.toLocaleString('id-ID') }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-muted-foreground">Rata-rata transaksi</span><span class="font-semibold">{{ formatIDR(summary.average_transaction) }}</span></div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b p-4">
                <div><h2 class="font-semibold">Transaksi berhasil terbaru</h2><p class="text-xs text-muted-foreground">Maksimal 15 transaksi terakhir dalam periode laporan.</p></div>
                <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600">{{ transactions.length }} ditampilkan</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground"><tr><th class="px-4 py-3 font-medium">Tanggal</th><th class="px-4 py-3 font-medium">Invoice</th><th class="px-4 py-3 font-medium">Pelanggan</th><th class="px-4 py-3 font-medium">Kategori</th><th class="px-4 py-3 font-medium">Metode</th><th class="px-4 py-3 text-right font-medium">Total</th></tr></thead>
                    <tbody class="divide-y">
                        <tr v-for="item in transactions" :key="item.id" class="hover:bg-muted/30">
                            <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ formatDateTime(item.created_at) }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ item.invoice_number || '—' }}</td>
                            <td class="px-4 py-3 font-medium">{{ item.customer }}</td>
                            <td class="px-4 py-3">{{ item.type_label }}</td>
                            <td class="px-4 py-3">{{ methodLabel(item.payment_method) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ formatIDR(item.amount) }}</td>
                        </tr>
                        <tr v-if="!transactions.length"><td colspan="6" class="px-4 py-12 text-center text-muted-foreground"><Receipt class="mx-auto size-9 opacity-40" /><p class="mt-3 font-medium">Belum ada transaksi berhasil</p><p class="mt-1 text-xs">Coba pilih rentang tanggal yang berbeda.</p></td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
