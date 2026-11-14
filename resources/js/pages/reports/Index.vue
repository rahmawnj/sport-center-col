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

type PaymentStatus = 'pending' | 'paid' | 'partial' | 'failed' | 'refunded';
type ReportType = { value: string; label: string };
type ReportRow = {
    id: number;
    transaction_id: number | null;
    invoice_number: string | null;
    customer_name: string | null;
    member_code?: string | null;
    package_name?: string;
    facility_name?: string;
    trainer_name?: string;
    court_name?: string;
    class_name?: string;
    start_date?: string | null;
    end_date?: string | null;
    membership_status?: string;
    purchase_status?: string;
    booking_status?: string;
    participant_status?: string;
    booking_date?: string | null;
    session_date?: string | null;
    start_time?: string;
    end_time?: string;
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
        data: ReportRow[];
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

const pageTitle = computed(() => props.types.find((item) => item.value === props.type)?.label ?? 'Laporan Pembelian');
const statCards = computed(() => [
    { label: 'Jumlah pembelian', value: props.summary.count, icon: Receipt, accent: 'sky' as const },
    { label: 'Nilai lunas', value: formatIDR(props.summary.paid), icon: CheckCircle2, accent: 'emerald' as const },
    { label: 'Menunggu pembayaran', value: formatIDR(props.summary.pending), icon: Clock, accent: 'amber' as const },
]);

const statusMeta: Record<PaymentStatus, { label: string; badge: string; dot: string }> = {
    paid: { label: 'Lunas', badge: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400', dot: 'bg-emerald-500' },
    pending: { label: 'Pending', badge: 'bg-amber-500/10 text-amber-600 dark:text-amber-400', dot: 'bg-amber-500' },
    partial: { label: 'DP / Sebagian', badge: 'bg-sky-500/10 text-sky-600 dark:text-sky-400', dot: 'bg-sky-500' },
    failed: { label: 'Gagal', badge: 'bg-red-500/10 text-red-600 dark:text-red-400', dot: 'bg-red-500' },
    refunded: { label: 'Refund', badge: 'bg-muted text-muted-foreground', dot: 'bg-muted-foreground/60' },
};

const methodLabel: Record<string, string> = { cash: 'Tunai', bank_transfer: 'Transfer' };
const stateLabel: Record<string, string> = {
    pending: 'Pending', active: 'Aktif', expired: 'Kedaluwarsa', cancelled: 'Dibatalkan',
    completed: 'Selesai', confirmed: 'Dikonfirmasi', attended: 'Hadir', no_show: 'Tidak hadir',
};

function applyFilter() {
    router.get(`/reports/${props.type}`, { from: from.value || undefined, to: to.value || undefined }, { preserveState: true, replace: true });
}
function reset() {
    from.value = '';
    to.value = '';
    router.get(`/reports/${props.type}`, {}, { preserveState: true, replace: true });
}
function formatIDR(value: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
}
function formatDateTime(value: string | null) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value));
}
function formatDate(value?: string | null) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(`${value}T00:00:00`));
}
function paymentLabel(value: PaymentStatus) {
    return statusMeta[value]?.label ?? value;
}
function stateClass(value?: string) {
    if (value === 'active' || value === 'confirmed' || value === 'attended' || value === 'completed') return 'bg-emerald-500/10 text-emerald-600';
    if (value === 'cancelled' || value === 'expired' || value === 'no_show') return 'bg-muted text-muted-foreground';
    return 'bg-amber-500/10 text-amber-600';
}
</script>

<template>
    <Head :title="pageTitle" />
    <div class="space-y-6 p-4 md:p-6">
        <PageHeader eyebrow="Laporan" :title="pageTitle" description="Rekap pembelian, pelanggan, dan status pembayaran." />

        <div class="flex flex-wrap gap-2">
            <Link
                v-for="tab in props.types"
                :key="tab.value"
                :href="`/reports/${tab.value}`"
                class="inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-medium transition-colors"
                :class="props.type === tab.value ? 'border-primary bg-primary/10 text-primary' : 'text-muted-foreground hover:border-primary hover:text-primary'"
            >
                <FileChartColumn class="size-4" />
                {{ tab.label }}
            </Link>
        </div>

        <SummaryCards :items="statCards" />

        <div class="rounded-2xl border bg-card p-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="grid gap-2">
                        <Label for="report-from">Dari tanggal pembelian</Label>
                        <Input id="report-from" v-model="from" type="date" class="sm:w-44" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="report-to">Sampai tanggal</Label>
                        <Input id="report-to" v-model="to" type="date" class="sm:w-44" />
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button variant="secondary" @click="applyFilter">Terapkan</Button>
                    <Button variant="ghost" @click="reset">Reset</Button>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-sm">
                    <thead class="border-b bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Tanggal pembelian</th>
                            <th class="px-4 py-3 font-medium">Invoice</th>
                            <th v-if="props.type !== 'booking'" class="px-4 py-3 font-medium">{{ props.type === 'class' ? 'Peserta' : 'Member' }}</th>
                            <th v-else class="px-4 py-3 font-medium">Pemesan</th>
                            <th v-if="props.type === 'membership'" class="px-4 py-3 font-medium">Kode member</th>
                            <th v-if="props.type === 'membership'" class="px-4 py-3 font-medium">Paket membership</th>
                            <th v-if="props.type === 'membership' || props.type === 'trainer' || props.type === 'booking' || props.type === 'class'" class="px-4 py-3 font-medium">Fasilitas</th>
                            <th v-if="props.type === 'trainer'" class="px-4 py-3 font-medium">Trainer</th>
                            <th v-if="props.type === 'trainer'" class="px-4 py-3 font-medium">Paket trainer</th>
                            <th v-if="props.type === 'booking'" class="px-4 py-3 font-medium">Lapangan</th>
                            <th v-if="props.type === 'booking'" class="px-4 py-3 font-medium">Tanggal & jam booking</th>
                            <th v-if="props.type === 'class'" class="px-4 py-3 font-medium">Kelas</th>
                            <th v-if="props.type === 'class'" class="px-4 py-3 font-medium">Trainer</th>
                            <th v-if="props.type === 'class'" class="px-4 py-3 font-medium">Tanggal & jam kelas</th>
                            <th v-if="props.type === 'membership'" class="px-4 py-3 font-medium">Masa membership</th>
                            <th v-if="props.type === 'membership' || props.type === 'trainer' || props.type === 'booking' || props.type === 'class'" class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Pembayaran</th>
                            <th class="px-4 py-3 font-medium">Metode</th>
                            <th class="px-4 py-3 text-right font-medium">Jumlah</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="row in props.transactions.data" :key="`${props.type}-${row.id}`" class="hover:bg-muted/30">
                            <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">{{ formatDateTime(row.created_at) }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ row.invoice_number || '—' }}</td>
                            <td class="px-4 py-3">{{ row.customer_name || 'Umum' }}</td>
                            <td v-if="props.type === 'membership'" class="px-4 py-3 font-mono text-xs">{{ row.member_code || '—' }}</td>
                            <td v-if="props.type === 'membership'" class="px-4 py-3">{{ row.package_name }}</td>
                            <td v-if="props.type === 'membership' || props.type === 'trainer' || props.type === 'booking' || props.type === 'class'" class="px-4 py-3">{{ row.facility_name }}</td>
                            <td v-if="props.type === 'trainer'" class="px-4 py-3">{{ row.trainer_name }}</td>
                            <td v-if="props.type === 'trainer'" class="px-4 py-3">{{ row.package_name }}</td>
                            <td v-if="props.type === 'booking'" class="px-4 py-3">{{ row.court_name }}</td>
                            <td v-if="props.type === 'booking'" class="whitespace-nowrap px-4 py-3">{{ formatDate(row.booking_date) }} · {{ row.start_time }}–{{ row.end_time }}</td>
                            <td v-if="props.type === 'class'" class="px-4 py-3">{{ row.class_name }}</td>
                            <td v-if="props.type === 'class'" class="px-4 py-3">{{ row.trainer_name }}</td>
                            <td v-if="props.type === 'class'" class="whitespace-nowrap px-4 py-3">{{ formatDate(row.session_date) }} · {{ row.start_time }}–{{ row.end_time }}</td>
                            <td v-if="props.type === 'membership'" class="whitespace-nowrap px-4 py-3">{{ formatDate(row.start_date) }} – {{ formatDate(row.end_date) }}</td>
                            <td v-if="props.type === 'membership'" class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="stateClass(row.membership_status)">{{ stateLabel[row.membership_status || ''] || row.membership_status }}</span></td>
                            <td v-if="props.type === 'trainer'" class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="stateClass(row.purchase_status)">{{ stateLabel[row.purchase_status || ''] || row.purchase_status }}</span></td>
                            <td v-if="props.type === 'booking'" class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="stateClass(row.booking_status)">{{ stateLabel[row.booking_status || ''] || row.booking_status }}</span></td>
                            <td v-if="props.type === 'class'" class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="stateClass(row.participant_status)">{{ stateLabel[row.participant_status || ''] || row.participant_status }}</span></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" :class="statusMeta[row.payment_status].badge">
                                    <span class="size-1.5 rounded-full" :class="statusMeta[row.payment_status].dot" />
                                    {{ paymentLabel(row.payment_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ row.payment_method ? (methodLabel[row.payment_method] ?? row.payment_method) : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium">{{ formatIDR(row.amount) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <Button v-if="row.transaction_id" variant="ghost" size="icon" title="Detail transaksi" class="hover:bg-primary/10 hover:text-primary" as-child>
                                        <Link :href="`/transactions/${row.transaction_id}`"><Eye class="size-4" /></Link>
                                    </Button>
                                    <span v-else class="text-muted-foreground">—</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!props.transactions.data.length">
                            <td colspan="15" class="px-4 py-12 text-center text-muted-foreground">
                                <FileChartColumn class="mx-auto size-10 text-muted-foreground/50" />
                                <p class="mt-3 font-medium">Belum ada data pembelian</p>
                                <p class="mt-1 text-sm">Coba ubah rentang tanggal.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="props.transactions.last_page > 1" class="flex flex-col gap-3 rounded-2xl border bg-card p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-muted-foreground">Halaman {{ props.transactions.current_page }} dari {{ props.transactions.last_page }} · {{ props.transactions.total }} data</p>
            <div class="flex flex-wrap gap-1">
                <a v-for="link in props.transactions.links" :key="link.label" :href="link.url ?? '#'" @click.prevent="link.url && router.get(link.url, {}, { preserveState: true })">
                    <Button size="sm" :variant="link.active ? 'default' : 'outline'" :disabled="!link.url"><span v-html="link.label" /></Button>
                </a>
            </div>
        </div>
    </div>
</template>
