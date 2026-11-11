<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight, CalendarDays, CreditCard, ReceiptText } from 'lucide-vue-next';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';

defineOptions({ layout: MemberDashboardLayout });

type Payment = {
    id: number;
    invoice_number: string | null;
    reference: string;
    amount: number;
    payment_method: string | null;
    payment_status: string;
    created_at: string | null;
};

defineProps<{
    payments: {
        data: Payment[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const currency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const dateLabel = (value: string | null) =>
    value
        ? new Date(value).toLocaleString('id-ID', {
              day: '2-digit',
              month: 'long',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : 'Tanggal tidak tersedia';

const statusLabel = (value: string) =>
    ({
        paid: 'Lunas',
        fully_paid: 'Lunas',
        pending: 'Menunggu pembayaran',
        unpaid: 'Belum dibayar',
        dp_paid: 'DP dibayar',
        failed: 'Gagal',
        refunded: 'Dikembalikan',
        cancelled: 'Dibatalkan',
        expired: 'Kedaluwarsa',
    }[value] || value);

const statusClass = (value: string) => {
    if (['paid', 'fully_paid'].includes(value)) return 'bg-emerald-50 text-emerald-700 ring-emerald-600/15';
    if (['pending', 'unpaid', 'dp_paid'].includes(value)) return 'bg-amber-50 text-amber-700 ring-amber-600/15';
    return 'bg-rose-50 text-rose-700 ring-rose-600/15';
};

const methodLabel = (value: string | null) =>
    ({
        cash: 'Tunai',
        bank_transfer: 'Transfer bank',
        qris: 'QRIS',
    }[value || ''] || 'Belum ditentukan');
</script>

<template>
    <Head title="Pembayaran Saya" />

    <div class="space-y-6">
        <header class="space-y-1">
            <p class="text-xs font-bold uppercase tracking-[.18em] text-orange-700">Riwayat transaksi</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pembayaran Saya</h1>
            <p class="text-sm text-slate-500">
                Pantau status pembayaran booking, kelas, dan membership kamu.
            </p>
        </header>

        <div v-if="payments.data.length" class="grid gap-4 md:grid-cols-2">
            <article
                v-for="item in payments.data"
                :key="item.id"
                class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-orange-200 hover:shadow-md"
            >
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="grid size-11 shrink-0 place-items-center rounded-xl bg-orange-50 text-orange-700">
                            <ReceiptText class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-900">{{ item.reference || 'Transaksi' }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-400">
                                {{ item.invoice_number || 'TRX-' + item.id }}
                            </p>
                        </div>
                    </div>
                    <span
                        class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset"
                        :class="statusClass(item.payment_status)"
                    >
                        {{ statusLabel(item.payment_status) }}
                    </span>
                </div>

                <div class="flex flex-1 flex-col px-5 py-5">
                    <p class="text-xs font-medium text-slate-500">Total transaksi</p>
                    <p class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">
                        {{ currency(item.amount) }}
                    </p>

                    <div class="mt-5 space-y-3 border-t border-dashed border-slate-200 pt-4">
                        <div class="flex items-center gap-2.5 text-sm text-slate-600">
                            <CalendarDays class="size-4 shrink-0 text-slate-400" />
                            <span>{{ dateLabel(item.created_at) }}</span>
                        </div>
                        <div class="flex items-center gap-2.5 text-sm text-slate-600">
                            <CreditCard class="size-4 shrink-0 text-slate-400" />
                            <span>{{ methodLabel(item.payment_method) }}</span>
                        </div>
                    </div>

                    <Link
                        :href="`/member/payments/${item.id}`"
                        class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#001428] px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2"
                    >
                        Lihat detail pembayaran
                        <ArrowUpRight class="size-4 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                    </Link>
                </div>
            </article>
        </div>

        <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-orange-50 text-orange-700">
                <CreditCard class="size-7" />
            </div>
            <h2 class="mt-4 font-bold text-slate-900">Belum ada transaksi</h2>
            <p class="mx-auto mt-1 max-w-sm text-sm leading-6 text-slate-500">
                Pembayaran yang terhubung dengan akunmu akan muncul di halaman ini.
            </p>
            <Link
                href="/#membership"
                class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#001428] px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
            >
                Lihat paket membership
                <ArrowUpRight class="size-4" />
            </Link>
        </div>

        <nav v-if="payments.links.length > 3" class="flex flex-wrap items-center justify-center gap-2">
            <template v-for="link in payments.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="rounded-lg border px-3 py-2 text-sm transition"
                    :class="link.active
                        ? 'border-orange-300 bg-orange-50 font-bold text-orange-800'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-orange-200 hover:bg-orange-50/50'"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="rounded-lg border border-slate-100 px-3 py-2 text-sm text-slate-300"
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>
</template>
