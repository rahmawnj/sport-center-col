<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle2, Clock, Eye, Receipt, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SummaryCards from '@/components/SummaryCards.vue';
import TableFilters from '@/components/TableFilters.vue';
import { Button } from '@/components/ui/button';

type PaymentStatus = 'pending' | 'paid' | 'failed' | 'refunded';
type Transaction = {
    id: number;
    invoice_number: string | null;
    customer_name: string | null;
    reference: string;
    amount: number;
    payment_method: string | null;
    payment_status: PaymentStatus;
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
    filters: { search: string; status: string };
    stats: { today: number; total: number; paid: number; pending: number };
};

const props = defineProps<Props>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');
const statusOptions = [
    { value: 'all', label: 'Semua status' },
    { value: 'paid', label: 'Lunas' },
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

function filter() {
    router.get(
        '/transactions',
        {
            search: search.value || undefined,
            status: status.value === 'all' ? undefined : status.value,
        },
        { preserveState: true, replace: true },
    );
}

function reset() {
    search.value = '';
    status.value = 'all';
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

        <SummaryCards :items="statCards" />

        <div class="space-y-4">
            <div class="rounded-2xl border bg-card p-4 shadow-sm">
                <TableFilters
                    v-model:search="search"
                    v-model:status="status"
                    search-placeholder="Cari nama pelanggan..."
                    :status-options="statusOptions"
                    @filter="filter"
                    @reset="reset"
                />
            </div>

            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Waktu</th>
                            <th class="px-4 py-3 font-medium">Invoice</th>
                            <th class="px-4 py-3 font-medium">Pelanggan</th>
                            <th class="px-4 py-3 font-medium">Referensi</th>
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
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ row.reference }}
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
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        title="Detail"
                                        class="hover:bg-primary/10 hover:text-primary"
                                        as-child
                                    >
                                        <Link :href="`/transactions/${row.id}`">
                                            <Eye class="size-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!props.transactions.data.length">
                            <td
                                colspan="8"
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
