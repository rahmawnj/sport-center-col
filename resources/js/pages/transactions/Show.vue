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
import { computed } from 'vue';
import QrcodeVue from 'qrcode.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';

type PaymentStatus = 'pending' | 'paid' | 'failed' | 'refunded';
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
    <Head :title="`Transaksi #${meta.id}`" />
    <div class="space-y-6 p-4 md:p-6">
        <Link
            href="/transactions"
            class="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
        >
            <ArrowLeft class="size-4" />
            Kembali ke transaksi
        </Link>

        <PageHeader
            eyebrow="Detail transaksi"
            :title="`Transaksi #${meta.id}`"
            :description="`Dibuat ${formatDateTime(meta.created_at)}`"
        >
            <span
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium"
                :class="status.badge"
            >
                <span class="size-1.5 rounded-full" :class="status.dot" />
                {{ status.label }}
            </span>
        </PageHeader>

        <div class="grid gap-4 lg:grid-cols-3">
            <!-- Amount highlight -->
            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br p-5 text-white shadow-lg lg:col-span-1"
                :class="status.gradient"
            >
                <div
                    class="pointer-events-none absolute -top-8 -right-8 size-28 rounded-full bg-white/15 blur-2xl"
                ></div>
                <p class="relative text-xs font-medium tracking-wide uppercase">
                    Total pembayaran
                </p>
                <p class="relative mt-2 text-3xl font-bold tracking-tight">
                    {{ formatIDR(meta.amount) }}
                </p>
                <div
                    class="relative mt-4 inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-xs font-medium backdrop-blur-sm"
                >
                    <CreditCard class="size-3.5" />
                    {{
                        meta.payment_method
                            ? (methodLabel[meta.payment_method] ??
                              meta.payment_method)
                            : 'Belum ada metode'
                    }}
                </div>
            </div>

            <!-- Info -->
            <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
                <div
                    class="flex items-center gap-3 rounded-2xl border bg-card p-4 shadow-sm"
                >
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400"
                    >
                        <User class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs text-muted-foreground">
                            Pelanggan
                        </div>
                        <div class="truncate font-medium">
                            {{ meta.customer_name || 'Umum / walk-in' }}
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center gap-3 rounded-2xl border bg-card p-4 shadow-sm"
                >
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400"
                    >
                        <Hash class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs text-muted-foreground">
                            Referensi
                        </div>
                        <div class="truncate font-medium">
                            {{ meta.reference }}
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center gap-3 rounded-2xl border bg-card p-4 shadow-sm"
                >
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                    >
                        <BadgeCheck class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs text-muted-foreground">Status</div>
                        <div class="font-medium">{{ status.label }}</div>
                    </div>
                </div>

                <div
                    class="flex items-center gap-3 rounded-2xl border bg-card p-4 shadow-sm"
                >
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400"
                    >
                        <Clock class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs text-muted-foreground">Waktu</div>
                        <div class="truncate font-medium">
                            {{ formatDateTime(meta.created_at) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice & QR -->
        <div
            class="grid gap-5 rounded-2xl border bg-card p-5 shadow-sm sm:grid-cols-[auto_1fr] sm:items-center"
        >
            <div class="flex justify-center">
                <div class="rounded-xl bg-white p-3 shadow-inner">
                    <QrcodeVue
                        :value="meta.qr_code ?? ''"
                        :size="140"
                        level="M"
                        render-as="svg"
                    />
                </div>
            </div>
            <div class="space-y-2 text-center sm:text-left">
                <p
                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    Nomor invoice
                </p>
                <p class="font-mono text-lg font-bold tracking-tight">
                    {{ meta.invoice_number || '—' }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Scan QR untuk memverifikasi keaslian transaksi ini.
                </p>
                <Button
                    v-if="meta.qr_code"
                    as-child
                    variant="outline"
                    size="sm"
                >
                    <a
                        :href="`/verify?code=${meta.qr_code}`"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Buka halaman verifikasi
                    </a>
                </Button>
            </div>
        </div>

        <!-- Item details -->
        <div
            v-if="meta.details.length"
            class="overflow-hidden rounded-2xl border bg-card shadow-sm"
        >
            <div class="border-b bg-muted/40 px-4 py-3 font-semibold">
                Detail item
            </div>
            <dl class="divide-y">
                <div
                    v-for="detail in meta.details"
                    :key="detail.label"
                    class="flex items-center justify-between gap-4 px-4 py-3 text-sm"
                >
                    <dt class="text-muted-foreground">{{ detail.label }}</dt>
                    <dd class="text-right font-medium">
                        {{ detail.value || '—' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div v-else class="flex justify-end">
            <Button as-child variant="outline">
                <Link href="/transactions">
                    <ArrowLeft class="mr-2 size-4" />
                    Kembali
                </Link>
            </Button>
        </div>
    </div>
</template>
