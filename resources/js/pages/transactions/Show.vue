<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    Clock,
    CreditCard,
    Hash,
    User,
} from '@lucide/vue';
import QrcodeVue from 'qrcode.vue';
import { computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';

type PaymentStatus = 'pending' | 'paid' | 'partial' | 'failed' | 'refunded';
type Detail = { label: string; value: string | null };
type Props = {
    transaction: {
        id: number;
        invoice_number: string | null;
        qr_code: string | null;
        customer_name: string | null;
        amount: number;
        payment_method: string | null;
        payment_status: PaymentStatus;
        reference: string;
        created_at: string | null;
        item_name: string;
        details: Detail[];
    };
};

const props = defineProps<Props>();

const statusMeta: Record<
    PaymentStatus,
    { label: string; badge: string; dot: string; gradient: string }
> = {
    paid: {
        label: 'Lunas',
        badge: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        dot: 'bg-emerald-500',
        gradient: 'from-emerald-400 to-teal-600 shadow-emerald-500/30',
    },
    partial: {
        label: 'DP / Sebagian',
        badge: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
        dot: 'bg-blue-500',
        gradient: 'from-blue-400 to-indigo-600 shadow-blue-500/30',
    },
    pending: {
        label: 'Pending',
        badge: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        dot: 'bg-amber-500',
        gradient: 'from-amber-400 to-orange-500 shadow-amber-500/30',
    },
    failed: {
        label: 'Gagal',
        badge: 'bg-red-500/10 text-red-600 dark:text-red-400',
        dot: 'bg-red-500',
        gradient: 'from-red-400 to-rose-600 shadow-red-500/30',
    },
    refunded: {
        label: 'Refund',
        badge: 'bg-muted text-muted-foreground',
        dot: 'bg-muted-foreground/60',
        gradient: 'from-slate-400 to-slate-600 shadow-slate-500/30',
    },
};

const methodLabel: Record<string, string> = {
    cash: 'Tunai',
    bank_transfer: 'Transfer Bank',
    qris: 'QRIS',
};

const meta = computed(() => props.transaction);
const status = computed(() => statusMeta[meta.value.payment_status]);

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
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}
</script>

<template>
    <Head :title="`Detail Transaksi #${meta.id}`" />
    <div class="mx-auto max-w-6xl space-y-5 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Link href="/transactions" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft class="size-4" />
                Kembali ke transaksi
            </Link>
            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium" :class="status.badge">
                <span class="size-2 rounded-full" :class="status.dot" />
                {{ status.label }}
            </span>
        </div>

        <div class="rounded-2xl border bg-card p-5 shadow-sm md:p-6">
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Detail transaksi · {{ meta.item_name }}</p>
                    <h1 class="mt-2 break-all font-mono text-xl font-semibold tracking-tight">{{ meta.invoice_number || 'Invoice belum tersedia' }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{{ formatDateTime(meta.created_at) }}</p>
                    <p class="mt-4 text-sm text-muted-foreground">Pelanggan</p>
                    <p class="font-medium">{{ meta.customer_name || 'Umum / walk-in' }}</p>
                </div>
                <div class="min-w-[220px] rounded-xl bg-muted/50 p-4">
                    <p class="text-xs text-muted-foreground">Total pembayaran</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight">{{ formatIDR(meta.amount) }}</p>
                    <div class="mt-3 flex items-center gap-2 text-sm text-muted-foreground">
                        <CreditCard class="size-4" />
                        {{ meta.payment_method ? (methodLabel[meta.payment_method] ?? meta.payment_method) : 'Belum ada metode' }}
                    </div>
                </div>
            </div>
            <div class="mt-5 grid gap-3 border-t pt-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-xs text-muted-foreground">Referensi</p>
                    <p class="mt-1 text-sm font-medium">{{ meta.reference }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Status pembayaran</p>
                    <p class="mt-1 text-sm font-medium">{{ status.label }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Waktu transaksi</p>
                    <p class="mt-1 text-sm font-medium">{{ formatDateTime(meta.created_at) }}</p>
                </div>
            </div>
        </div>

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_220px]">
            <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="border-b px-5 py-4">
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Rincian pesanan</p>
                    <h2 class="mt-1 text-lg font-semibold">{{ meta.item_name }}</h2>
                </div>
                <dl v-if="meta.details.length" class="divide-y">
                    <div v-for="detail in meta.details" :key="detail.label" class="grid gap-1 px-5 py-3 sm:grid-cols-[190px_minmax(0,1fr)] sm:gap-4">
                        <dt class="text-sm text-muted-foreground">{{ detail.label }}</dt>
                        <dd class="break-words text-sm font-medium sm:text-right">{{ detail.value || '—' }}</dd>
                    </div>
                </dl>
                <div v-else class="px-5 py-8 text-center text-sm text-muted-foreground">Rincian pesanan belum tersedia.</div>
            </section>

            <aside v-if="meta.qr_code" class="rounded-2xl border bg-card p-4 text-center shadow-sm">
                <p class="text-sm font-semibold">QR verifikasi</p>
                <div class="mx-auto mt-3 inline-flex rounded-xl bg-white p-3">
                    <QrcodeVue :value="meta.qr_code" :size="150" level="M" render-as="svg" />
                </div>
                <p class="mt-2 break-all font-mono text-xs text-muted-foreground">{{ meta.qr_code }}</p>
                <Button as-child variant="outline" size="sm" class="mt-3 w-full">
                    <a :href="`/verify?code=${meta.qr_code}`" target="_blank" rel="noopener noreferrer">Buka verifikasi</a>
                </Button>
            </aside>
        </div>
    </div>
</template>
