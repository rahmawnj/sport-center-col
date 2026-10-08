<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Clock,
    Eye,
    FileChartColumn,
    Receipt,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import SummaryCards from '@/components/SummaryCards.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type PaymentStatus = 'pending' | 'paid' | 'failed' | 'refunded';
type ReportType = { value: string; label: string };
type Transaction = {
    id: number;
    invoice_number: string | null;
    customer_name: string | null;
    amount: number;
    payment_method: string | null;
    payment_status: PaymentStatus;
    created_at: string | null;
};
type LinkItem = { url: string | null; label: string; active: boolean };
type Props = {
    type: string;
    types: ReportType[];
    transactions: {
        data: Transaction[];
        current_page: number;
        last_page: number;
        total: number;
        links: LinkItem[];
    };
    summary: { count: number; paid: number; pending: number };
    filters: { from: string | null; to: string | null };
};

const props = defineProps<Props>();

const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

const statCards = computed(() => [
    {
        label: 'Total transaksi',
        value: props.summary.count,
        icon: Receipt,
        accent: 'sky' as const,
    },
    {
        label: 'Nilai lunas',
        value: formatIDR(props.summary.paid),
        icon: CheckCircle2,
        accent: 'emerald' as const,
    },
    {
        label: 'Nilai pending',
        value: formatIDR(props.summary.pending),
        icon: Clock,
        accent: 'amber' as const,
    },
]);

const statusMeta: Record<
    PaymentStatus,
    { label: string; badge: string; dot: string }
> = {
    paid: {
        label: 'Lunas',
        badge: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        dot: 'bg-emerald-500',
    },
    pending: {
        label: 'Pending',
        badge: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        dot: 'bg-amber-500',
    },
    failed: {
        label: 'Gagal',
        badge: 'bg-red-500/10 text-red-600 dark:text-red-400',
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

function applyFilter() {
    router.get(
        `/reports/${props.type}`,
        {
            from: from.value || undefined,
            to: to.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function reset() {
    from.value = '';
    to.value = '';
    router.get(
        `/reports/${props.type}`,
        {},
        { preserveState: true, replace: true },
    );
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
    <Head title="Laporan" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Laporan"
            title="Laporan Transaksi"
            description="Rekap transaksi per jenis penjualan."
        />

        <!-- Type tabs -->
        <div class="flex flex-wrap gap-2">
            <Link
                v-for="tab in props.types"
                :key="tab.value"
                :href="`/reports/${tab.value}`"
                class="inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-medium transition-colors"
                :class="
                    props.type === tab.value
                        ? 'border-primary bg-primary/10 text-primary'
                        : 'text-muted-foreground hover:border-primary hover:text-primary'
                "
            >
                <FileChartColumn class="size-4" />
                {{ tab.label }}
            </Link>
        </div>

        <SummaryCards :items="statCards" />

        <div class="space-y-4">
            <div class="rounded-2xl border bg-card p-4 shadow-sm">
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="grid gap-2">
                            <Label for="report-from">Dari tanggal</Label>
                            <Input
                                id="report-from"
                                v-model="from"
                                type="date"
                                class="sm:w-44"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="report-to">Sampai tanggal</Label>
                            <Input
                                id="report-to"
                                v-model="to"
                                type="date"
                                class="sm:w-44"
                            />
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="secondary" @click="applyFilter"
                            >Terapkan</Button
                        >
                        <Button variant="ghost" @click="reset">Reset</Button>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Waktu</th>
                            <th class="px-4 py-3 font-medium">Invoice</th>
                            <th class="px-4 py-3 font-medium">Pelanggan</th>
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
                            <td class="px-4 py-3 font-mono text-xs">
                                {{ row.invoice_number || '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ row.customer_name || 'Umum' }}
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
                                colspan="7"
                                class="px-4 py-12 text-center text-muted-foreground"
                            >
                                <FileChartColumn
                                    class="mx-auto size-10 text-muted-foreground/50"
                                />
                                <p class="mt-3 font-medium">
                                    Belum ada data laporan
                                </p>
                                <p class="mt-1 text-sm">
                                    Coba ubah rentang tanggal.
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
