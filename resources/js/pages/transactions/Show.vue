<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, ReceiptText } from '@lucide/vue';
import QrcodeVue from 'qrcode.vue';
import { computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';

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

const statusMeta: Record<PaymentStatus, { label: string; badge: string; dot: string }> = {
    paid: {
        label: 'Lunas',
        badge: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        dot: 'bg-emerald-500',
    },
    partial: {
        label: 'DP / Sebagian',
        badge: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
        dot: 'bg-blue-500',
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
    bank_transfer: 'Transfer Bank',
    qris: 'QRIS',
};

const meta = computed(() => props.transaction);
const status = computed(() => statusMeta[meta.value.payment_status] ?? statusMeta.pending);

function formatIDR(value: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
}

function formatDateTime(value: string | null) {
    if (!value) return '—';

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

    <div class="space-y-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Keuangan"
            title="Detail Transaksi"
            description="Informasi pembayaran dan rincian pesanan."
        >
            <Link
                href="/transactions"
                class="inline-flex h-10 items-center gap-2 rounded-lg border bg-background px-4 text-sm font-medium transition-colors hover:bg-muted"
            >
                <ArrowLeft class="size-4" />
                Kembali
            </Link>
        </PageHeader>

        <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div class="flex flex-col gap-5 p-5 md:flex-row md:items-center md:justify-between md:p-6">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold" :class="status.badge">
                            <span class="size-1.5 rounded-full" :class="status.dot" />
                            {{ status.label }}
                        </span>
                        <span class="text-xs text-muted-foreground">Transaksi #{{ meta.id }}</span>
                    </div>
                    <h2 class="mt-3 break-all font-mono text-lg font-semibold tracking-tight md:text-xl">
                        {{ meta.invoice_number || 'Invoice belum tersedia' }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ formatDateTime(meta.created_at) }}</p>
                </div>

                <div class="rounded-xl bg-muted/50 px-5 py-4 md:min-w-[250px]">
                    <p class="text-xs font-medium text-muted-foreground">Total pembayaran</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight tabular-nums">{{ formatIDR(meta.amount) }}</p>
                    <div class="mt-2 flex items-center gap-2 text-sm text-muted-foreground">
                        <CreditCard class="size-4 shrink-0" />
                        {{ meta.payment_method ? (methodLabel[meta.payment_method] ?? meta.payment_method) : 'Belum ada metode' }}
                    </div>
                </div>
            </div>

            <div class="grid border-t sm:grid-cols-3">
                <div class="border-b p-5 sm:border-b-0 sm:border-r">
                    <p class="text-xs text-muted-foreground">Pelanggan</p>
                    <p class="mt-1 break-words text-sm font-semibold">{{ meta.customer_name || 'Umum / walk-in' }}</p>
                </div>
                <div class="border-b p-5 sm:border-b-0 sm:border-r">
                    <p class="text-xs text-muted-foreground">Referensi</p>
                    <p class="mt-1 break-all font-mono text-sm font-semibold">{{ meta.reference || '—' }}</p>
                </div>
                <div class="p-5">
                    <p class="text-xs text-muted-foreground">Status pembayaran</p>
                    <p class="mt-1 text-sm font-semibold">{{ status.label }}</p>
                </div>
            </div>
        </section>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section class="min-w-0 overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="flex items-center gap-3 border-b p-5">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <ReceiptText class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Rincian pesanan</p>
                        <h3 class="mt-0.5 break-words font-semibold">{{ meta.item_name }}</h3>
                    </div>
                </div>

                <dl v-if="meta.details.length" class="divide-y">
                    <div
                        v-for="detail in meta.details"
                        :key="detail.label"
                        class="grid gap-1 px-5 py-4 sm:grid-cols-[minmax(150px,0.38fr)_minmax(0,1fr)] sm:gap-6"
                    >
                        <dt class="text-sm text-muted-foreground">{{ detail.label }}</dt>
                        <dd class="min-w-0 break-words text-sm font-medium sm:text-right">{{ detail.value || '—' }}</dd>
                    </div>
                </dl>
                <div v-else class="px-5 py-12 text-center text-sm text-muted-foreground">
                    Rincian pesanan belum tersedia.
                </div>
            </section>

            <aside class="space-y-6">
                <section v-if="meta.qr_code" class="rounded-2xl border bg-card p-5 text-center shadow-sm">
                    <h3 class="font-semibold">QR Verifikasi</h3>
                    <p class="mt-1 text-xs text-muted-foreground">Pindai QR untuk membuka verifikasi transaksi.</p>
                    <div class="mx-auto mt-5 inline-flex max-w-full rounded-xl bg-white p-3">
                        <QrcodeVue :value="meta.qr_code" :size="190" level="M" render-as="svg" />
                    </div>
                    <p class="mt-3 break-all font-mono text-xs text-muted-foreground">{{ meta.qr_code }}</p>
                    <a
                        :href="`/verify?code=${encodeURIComponent(meta.qr_code)}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-4 inline-flex h-10 w-full items-center justify-center rounded-lg border bg-background px-4 text-sm font-medium transition-colors hover:bg-muted"
                    >
                        Buka halaman verifikasi
                    </a>
                </section>

                <section class="rounded-2xl border bg-card p-5 shadow-sm">
                    <h3 class="font-semibold">Informasi pembayaran</h3>
                    <div class="mt-4 space-y-4">
                        <div class="flex items-start justify-between gap-4">
                            <span class="text-sm text-muted-foreground">Metode</span>
                            <span class="text-right text-sm font-medium">{{ meta.payment_method ? (methodLabel[meta.payment_method] ?? meta.payment_method) : '—' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <span class="text-sm text-muted-foreground">Waktu transaksi</span>
                            <span class="text-right text-sm font-medium">{{ formatDateTime(meta.created_at) }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-t pt-4">
                            <span class="text-sm text-muted-foreground">Total</span>
                            <span class="text-right text-base font-bold tabular-nums">{{ formatIDR(meta.amount) }}</span>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>
