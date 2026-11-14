<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CalendarRange, CheckCircle2, Clock, Eye, Pencil, Receipt, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SummaryCards from '@/components/SummaryCards.vue';
import TableFilters from '@/components/TableFilters.vue';
import { Button } from '@/components/ui/button';

type PaymentStatus = 'pending' | 'paid' | 'partial' | 'failed' | 'refunded';
type Transaction = {
    id: number;
    invoice_number: string | null;
    customer_name: string | null;
    reference: string;
    amount: number;
    payment_method: string | null;
    payment_status: PaymentStatus;
    type: string;
    type_label: string;
    item_summary: string;
    scan_count: number | null;
    created_at: string | null;
};
type LinkItem = { url: string | null; label: string; active: boolean };
type Props = {
    transactions: {
        data: Transaction[];
        current_page: number;
        last_page: number;
        total: number;
        links: LinkItem[];
    };
    filters: { search: string; status: string; from: string | null; to: string | null };
    stats: { today: number; total: number; paid: number; pending: number };
};

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string | null; error?: string | null });

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const rangeOpen = ref(false);
const datePresets = [
    { value: 'today', label: 'Hari ini' },
    { value: '7days', label: '7 hari terakhir' },
    { value: '30days', label: '30 hari terakhir' },
    { value: 'month', label: 'Bulan ini' },
] as const;
const rangeStart = ref(from.value);
const rangeEnd = ref(to.value);
const viewMonth = ref(monthStart(from.value || toISO(new Date())));
const statusOptions = [
    { value: 'all', label: 'Semua status' },
    { value: 'paid', label: 'Lunas' },
    { value: 'partial', label: 'DP / Sebagian' },
    { value: 'pending', label: 'Pending' },
    { value: 'failed', label: 'Gagal' },
    { value: 'refunded', label: 'Refund' },
];

const statusMeta: Record<
    PaymentStatus,
    { label: string; badge: string; dot: string }
> = {
    paid: {
        label: 'Lunas',
        badge: 'bg-emerald-500/10 text-emerald-600',
        dot: 'bg-emerald-500',
    },
    pending: {
        label: 'Pending',
        badge: 'bg-amber-500/10 text-amber-600',
        dot: 'bg-amber-500',
    },
    partial: {
        label: 'DP / Sebagian',
        badge: 'bg-sky-500/10 text-sky-600',
        dot: 'bg-sky-500',
    },
    failed: {
        label: 'Gagal',
        badge: 'bg-red-500/10 text-red-600',
        dot: 'bg-red-500',
    },
    refunded: {
        label: 'Refund',
        badge: 'bg-muted text-muted-foreground',
        dot: 'bg-muted-foreground/60',
    },
};

const methodLabel: Record<string, string> = {
    cash: 'Tunai',
    bank_transfer: 'Transfer',
    qris: 'QRIS',
};

const statCards = computed(() => [
    {
        label: 'Pendapatan hari ini',
        value: formatIDR(props.stats.today),
        icon: Wallet,
        accent: 'emerald' as const,
    },
    {
        label: 'Total transaksi',
        value: props.stats.total,
        icon: Receipt,
        accent: 'sky' as const,
    },
    {
        label: 'Lunas',
        value: props.stats.paid,
        icon: CheckCircle2,
        accent: 'violet' as const,
    },
    {
        label: 'Pending',
        value: props.stats.pending,
        icon: Clock,
        accent: 'amber' as const,
    },
]);

function parseDate(value: string) {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
}
function toISO(date: Date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}
function monthStart(value: string | Date) {
    const date = typeof value === 'string' ? parseDate(value) : value;
    return new Date(date.getFullYear(), date.getMonth(), 1);
}
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
    if (!from.value && !to.value) return 'Pilih rentang tanggal';
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
    filter();
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
    filter();
}

function filter() {
    router.get(
        '/transactions',
        {
            search: search.value || undefined,
            status: status.value === 'all' ? undefined : status.value,
            from: from.value || undefined,
            to: to.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function reset() {
    search.value = '';
    status.value = 'all';
    from.value = '';
    to.value = '';
    rangeStart.value = '';
    rangeEnd.value = '';
    rangeOpen.value = false;
    filter();
}

function formatIDR(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function formatDateTime(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}
</script>

<template>
    <Head title="Transaksi" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Keuangan"
            title="Transaksi"
            description="Riwayat transaksi dan pembayaran di Sport Center."
        >
            <Button as-child>
                <Link href="/transactions/create">
                    <Receipt class="mr-2 size-4" />
                    Buka Kasir
                </Link>
            </Button>
        </PageHeader>

        <div v-if="flash.success || flash.error" class="space-y-2" aria-live="polite">
            <div v-if="flash.success" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="rounded-xl border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
                {{ flash.error }}
            </div>
        </div>

        <SummaryCards :items="statCards" />

        <div class="space-y-4">
            <div class="rounded-2xl border bg-card p-4 shadow-sm">
                <div class="flex flex-col gap-3">
                    <TableFilters
                        v-model:search="search"
                        v-model:status="status"
                        search-placeholder="Cari nama pelanggan..."
                        :status-options="statusOptions"
                        @filter="filter"
                        @reset="reset"
                    >
                        <template #after-search>
                            <div class="relative">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    class="size-10 shrink-0"
                                    title="Filter rentang tanggal"
                                    aria-label="Filter rentang tanggal"
                                    :aria-expanded="rangeOpen"
                                    @click="rangeOpen = !rangeOpen"
                                >
                                    <CalendarRange class="size-4" />
                                </Button>
                                <div v-if="rangeOpen" class="absolute left-0 z-30 mt-2 w-[min(680px,calc(100vw-2.5rem))] rounded-xl border bg-popover p-4 text-popover-foreground shadow-xl">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <p class="text-sm font-medium">{{ rangeLabel }}</p>
                                        <button type="button" class="text-xs font-medium text-primary hover:underline" @click="reset">Hapus rentang</button>
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
                                    <div class="mt-4 border-t pt-3 text-xs text-muted-foreground">
                                        {{ rangeStart ? formatDate(rangeStart) : 'Tanggal awal' }} – {{ rangeEnd ? formatDate(rangeEnd) : 'Tanggal akhir' }}
                                    </div>
                                </div>
                            </div>
                        </template>
                    </TableFilters>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                <table class="w-full min-w-[1250px] text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Waktu</th>
                            <th class="px-4 py-3 font-medium">Invoice</th>
                            <th class="px-4 py-3 font-medium">Pelanggan</th>
                            <th class="px-4 py-3 font-medium">Jenis & detail pesanan</th>
                            <th class="px-4 py-3 font-medium">Referensi</th>
                            <th class="px-4 py-3 font-medium">Jumlah Scan</th>
                            <th class="px-4 py-3 font-medium">Metode</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Jumlah
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="row in props.transactions.data"
                            :key="row.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatDateTime(row.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs">
                                    {{ row.invoice_number || '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                {{ row.customer_name || 'Umum' }}
                            </td>
                            <td class="min-w-[240px] px-4 py-3">
                                <div class="font-medium">{{ row.type_label }}</div>
                                <div class="mt-0.5 max-w-[360px] text-xs text-muted-foreground">{{ row.item_summary }}</div>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ row.reference }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ row.scan_count === null ? "—" : row.scan_count + " kali" }}
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    row.payment_method
                                        ? (methodLabel[row.payment_method] ??
                                          row.payment_method)
                                        : '—'
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        statusMeta[row.payment_status].badge
                                    "
                                >
                                    <span
                                        class="size-1.5 rounded-full"
                                        :class="
                                            statusMeta[row.payment_status].dot
                                        "
                                    />
                                    {{ statusMeta[row.payment_status].label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium">
                                {{ formatIDR(row.amount) }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            title="Ubah transaksi"
                                            class="gap-2"
                                            as-child
                                        >
                                            <Link :href="`/transactions/${row.id}/edit`">
                                                <Pencil class="size-4" />
                                                Ubah
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            title="Lihat detail transaksi"
                                            class="gap-2"
                                            as-child
                                        >
                                            <Link :href="`/transactions/${row.id}`">
                                                <Eye class="size-4" />
                                                Detail
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!props.transactions.data.length">
                            <td
                                colspan="10"
                                class="px-4 py-12 text-center text-muted-foreground"
                            >
                                <Receipt
                                    class="mx-auto size-10 text-muted-foreground/50"
                                />
                                <p class="mt-3 font-medium">
                                    Belum ada transaksi
                                </p>
                                <p class="mt-1 text-sm">
                                    Buka kasir untuk mencatat transaksi baru.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <div
                v-if="props.transactions.last_page > 1"
                class="flex flex-col gap-3 rounded-2xl border bg-card p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-xs text-muted-foreground">
                    Halaman {{ props.transactions.current_page }} dari
                    {{ props.transactions.last_page }} ·
                    {{ props.transactions.total }} transaksi
                </p>
                <div class="flex flex-wrap gap-1">
                    <a
                        v-for="link in props.transactions.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        @click.prevent="
                            link.url &&
                            router.get(link.url, {}, { preserveState: true })
                        "
                    >
                        <Button
                            size="sm"
                            :variant="link.active ? 'default' : 'outline'"
                            :disabled="!link.url"
                        >
                            <span v-html="link.label" />
                        </Button>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
