<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle2, CircleDollarSign, FileChartColumn, Receipt, RotateCcw, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import SummaryCards from '@/components/SummaryCards.vue';

type Summary = { transaction_count: number; revenue: number; average_transaction: number };
type TypeRow = { type: string; label: string; transaction_count: number; revenue: number };
type TransactionRow = {
    id: number;
    invoice_number: string | null;
    customer: string;
    type: string | null;
    type_label: string;
    payment_method: string | null;
    amount: number;
    created_at: string | null;
};

const props = defineProps<{
    summary: Summary;
    typeRows: TypeRow[];
    transactions: TransactionRow[];
    filters: { from: string | null; to: string | null };
}>();

const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const isFiltered = computed(() => !!from.value || !!to.value);

const formatIDR = (value: number) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(value);

const formatDateTime = (value: string | null) => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    }).format(new Date(value.replace(' ', 'T')));
};

const methodLabel = (value: string | null) => ({
    cash: 'Tunai',
    bank_transfer: 'Transfer bank',
}[value ?? ''] ?? value ?? '—');

function applyFilter() {
    router.get('/dashboard', {
        from: from.value || undefined,
        to: to.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

function resetFilter() {
    from.value = '';
    to.value = '';
    router.get('/dashboard', {}, { preserveState: true, preserveScroll: true, replace: true });
}

const cards = computed(() => [
    { label: 'Total pendapatan lunas', value: formatIDR(props.summary.revenue), icon: CircleDollarSign, accent: 'emerald' as const },
    { label: 'Transaksi berhasil', value: props.summary.transaction_count, icon: CheckCircle2, accent: 'sky' as const },
    { label: 'Rata-rata transaksi', value: formatIDR(props.summary.average_transaction), icon: Receipt, accent: 'violet' as const },
]);
</script>

<template>
    <Head title="Dashboard Statistik" />
    <div class="space-y-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-primary">
                    <span class="size-2 rounded-full bg-primary"></span> Laporan penjualan
                </div>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight">Dashboard Statistik</h1>
                <p class="mt-1 text-sm text-muted-foreground">Rekap transaksi berhasil dan pendapatan berdasarkan periode yang dipilih.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1.5 text-xs font-medium text-emerald-600">
                <CheckCircle2 class="size-3.5" /> Hanya transaksi lunas & terverifikasi
            </span>
        </div>

        <section class="rounded-2xl border bg-card p-4 shadow-sm">
            <div class="mb-3 flex items-center gap-2">
                <FileChartColumn class="size-4 text-muted-foreground" />
                <h2 class="text-sm font-semibold">Filter rentang tanggal</h2>
            </div>
            <div class="flex flex-col gap-3 md:flex-row md:items-end">
                <label class="grid flex-1 gap-1.5 text-xs font-medium text-muted-foreground">
                    Dari tanggal
                    <input v-model="from" type="date" class="h-10 rounded-lg border bg-background px-3 text-sm text-foreground outline-none focus:border-primary" />
                </label>
                <label class="grid flex-1 gap-1.5 text-xs font-medium text-muted-foreground">
                    Sampai tanggal
                    <input v-model="to" type="date" :min="from || undefined" class="h-10 rounded-lg border bg-background px-3 text-sm text-foreground outline-none focus:border-primary" />
                </label>
                <div class="flex gap-2">
                    <button type="button" @click="applyFilter" class="inline-flex h-10 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90">
                        <Search class="size-4" /> Terapkan
                    </button>
                    <button type="button" @click="resetFilter" class="inline-flex h-10 items-center gap-2 rounded-lg border px-4 text-sm font-medium hover:bg-muted">
                        <RotateCcw class="size-4" /> Reset
                    </button>
                </div>
            </div>
            <p class="mt-3 text-xs text-muted-foreground">
                {{ isFiltered ? 'Laporan difilter berdasarkan tanggal transaksi.' : 'Menampilkan seluruh riwayat transaksi berhasil.' }}
            </p>
        </section>

        <SummaryCards :items="cards" />

        <section class="space-y-3">
            <div>
                <h2 class="font-semibold">Ringkasan berdasarkan jenis transaksi</h2>
                <p class="text-xs text-muted-foreground">Jumlah transaksi dan pendapatan dari masing-masing layanan.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="item in typeRows" :key="item.type" class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-sm font-medium text-muted-foreground">{{ item.label }}</p>
                    <p class="mt-3 text-2xl font-semibold tabular-nums">{{ formatIDR(item.revenue) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ item.transaction_count }} transaksi berhasil</p>
                </article>
                <div v-if="!typeRows.length" class="col-span-full rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                    Belum ada transaksi berhasil pada periode ini.
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b p-4">
                <div>
                    <h2 class="font-semibold">Transaksi berhasil</h2>
                    <p class="text-xs text-muted-foreground">Maksimal 15 transaksi terbaru pada periode terpilih.</p>
                </div>
                <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600">{{ transactions.length }} ditampilkan</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="border-b bg-muted/40 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">Invoice</th>
                            <th class="px-4 py-3 font-medium">Pelanggan</th>
                            <th class="px-4 py-3 font-medium">Jenis transaksi</th>
                            <th class="px-4 py-3 font-medium">Metode</th>
                            <th class="px-4 py-3 text-right font-medium">Total</th>
                            <th class="px-4 py-3 text-right font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="item in transactions" :key="item.id" class="hover:bg-muted/30">
                            <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ formatDateTime(item.created_at) }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ item.invoice_number || '—' }}</td>
                            <td class="px-4 py-3 font-medium">{{ item.customer }}</td>
                            <td class="px-4 py-3">{{ item.type_label }}</td>
                            <td class="px-4 py-3">{{ methodLabel(item.payment_method) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ formatIDR(item.amount) }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-600">
                                    <CheckCircle2 class="size-3.5" /> Berhasil
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!transactions.length">
                            <td colspan="7" class="px-4 py-12 text-center text-muted-foreground">
                                <Receipt class="mx-auto size-9 opacity-40" />
                                <p class="mt-3 font-medium">Belum ada transaksi berhasil</p>
                                <p class="mt-1 text-xs">Coba pilih rentang tanggal yang berbeda.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
