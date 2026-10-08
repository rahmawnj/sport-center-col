<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BadgeCheck, LayoutGrid, ShieldAlert, ShieldCheck } from '@lucide/vue';

type PaymentStatus = 'pending' | 'paid' | 'failed' | 'refunded';
type Props = {
    code: string;
    found: boolean;
    transaction: {
        invoice_number: string;
        customer_name: string | null;
        amount: number;
        payment_method: string | null;
        payment_status: PaymentStatus;
        created_at: string | null;
    } | null;
};

const props = defineProps<Props>();

const statusMeta: Record<PaymentStatus, { label: string; badge: string }> = {
    paid: {
        label: 'Lunas',
        badge: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    },
    pending: {
        label: 'Pending',
        badge: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    },
    failed: {
        label: 'Gagal',
        badge: 'bg-red-500/10 text-red-600 dark:text-red-400',
    },
    refunded: {
        label: 'Refund',
        badge: 'bg-muted text-muted-foreground',
    },
};

const methodLabel: Record<string, string> = {
    cash: 'Tunai',
    bank_transfer: 'Transfer Bank',
    qris: 'QRIS',
};

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
    <Head title="Verifikasi Transaksi" />
    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b">
            <div
                class="mx-auto flex h-16 w-full max-w-3xl items-center justify-between px-4"
            >
                <Link href="/" class="flex items-center gap-2 font-semibold">
                    <LayoutGrid class="size-5 text-primary" />
                    Sport Center
                </Link>
                <span class="text-sm text-muted-foreground"
                    >Verifikasi Transaksi</span
                >
            </div>
        </header>

        <main class="mx-auto w-full max-w-3xl px-4 py-10">
            <div
                v-if="props.found && props.transaction"
                class="overflow-hidden rounded-2xl border bg-card shadow-sm"
            >
                <div
                    class="flex items-center gap-3 border-b bg-gradient-to-br from-emerald-400 to-teal-600 p-5 text-white"
                >
                    <div
                        class="flex size-11 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm"
                    >
                        <ShieldCheck class="size-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium tracking-wide uppercase">
                            Transaksi terverifikasi
                        </p>
                        <p class="text-lg font-bold">
                            {{ props.transaction.invoice_number }}
                        </p>
                    </div>
                </div>

                <dl class="divide-y">
                    <div
                        class="flex items-center justify-between gap-4 px-5 py-3 text-sm"
                    >
                        <dt class="text-muted-foreground">Pelanggan</dt>
                        <dd class="text-right font-medium">
                            {{ props.transaction.customer_name || 'Umum' }}
                        </dd>
                    </div>
                    <div
                        class="flex items-center justify-between gap-4 px-5 py-3 text-sm"
                    >
                        <dt class="text-muted-foreground">Total</dt>
                        <dd class="text-right font-medium">
                            {{ formatIDR(props.transaction.amount) }}
                        </dd>
                    </div>
                    <div
                        class="flex items-center justify-between gap-4 px-5 py-3 text-sm"
                    >
                        <dt class="text-muted-foreground">Metode</dt>
                        <dd class="text-right font-medium">
                            {{
                                props.transaction.payment_method
                                    ? (methodLabel[
                                          props.transaction.payment_method
                                      ] ?? props.transaction.payment_method)
                                    : '—'
                            }}
                        </dd>
                    </div>
                    <div
                        class="flex items-center justify-between gap-4 px-5 py-3 text-sm"
                    >
                        <dt class="text-muted-foreground">Status</dt>
                        <dd>
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="
                                    statusMeta[props.transaction.payment_status]
                                        .badge
                                "
                            >
                                {{
                                    statusMeta[props.transaction.payment_status]
                                        .label
                                }}
                            </span>
                        </dd>
                    </div>
                    <div
                        class="flex items-center justify-between gap-4 px-5 py-3 text-sm"
                    >
                        <dt class="text-muted-foreground">Waktu</dt>
                        <dd class="text-right font-medium">
                            {{ formatDateTime(props.transaction.created_at) }}
                        </dd>
                    </div>
                </dl>

                <div
                    class="flex items-center gap-2 border-t bg-muted/40 px-5 py-3 text-xs text-muted-foreground"
                >
                    <BadgeCheck class="size-4 text-emerald-500" />
                    Data transaksi cocok dengan sistem Sport Center.
                </div>
            </div>

            <div
                v-else
                class="rounded-2xl border bg-card p-10 text-center shadow-sm"
            >
                <div
                    class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-red-500/10 text-red-600 dark:text-red-400"
                >
                    <ShieldAlert class="size-7" />
                </div>
                <p class="mt-4 font-medium">Transaksi tidak ditemukan</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Kode
                    <span class="font-mono">{{ props.code || '—' }}</span>
                    tidak terdaftar atau tidak valid.
                </p>
            </div>
        </main>
    </div>
</template>
