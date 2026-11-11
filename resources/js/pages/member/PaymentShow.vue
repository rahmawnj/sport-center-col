<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, CreditCard, FileText, MapPin, UserRound, XCircle } from '@lucide/vue';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';

defineOptions({ layout: MemberDashboardLayout });

type Payment = {
    id: number;
    invoice_number: string | null;
    customer_name: string | null;
    transaction_type: string | null;
    class_details: {
        name: string | null;
        facility: string | null;
        trainer: string | null;
        date: string | null;
        start_time: string;
        end_time: string;
    } | null;
    amount: number;
    payment_method: string | null;
    payment_status: string;
    booking_status: string | null;
    can_cancel: boolean;
    cancelled_at: string | null;
    created_at: string | null;
};

const props = defineProps<{ payment: Payment }>();

const cancelPayment = () => {
    if (!window.confirm('Yakin ingin membatalkan pembayaran ini? Pendaftaran kelas atau paket terkait juga akan dibatalkan.')) return;
    router.post('/member/payments/' + props.payment.id + '/cancel');
};

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
        : '—';

const classDateLabel = (value: string | null) =>
    value
        ? new Date(value + 'T00:00:00').toLocaleDateString('id-ID', {
              weekday: 'long',
              day: '2-digit',
              month: 'long',
              year: 'numeric',
          })
        : '—';

const statusLabel = (value: string) =>
    ({
        fully_paid: 'Lunas',
        paid: 'Lunas',
        unpaid: 'Belum dibayar',
        pending: 'Menunggu pembayaran',
        dp_paid: 'DP dibayar',
        cancelled: 'Dibatalkan',
        failed: 'Gagal',
        expired: 'Kedaluwarsa',
        refunded: 'Dikembalikan',
    }[value] || value);

const statusClass = (value: string) => {
    if (['fully_paid', 'paid'].includes(value)) return 'bg-emerald-100 text-emerald-800';
    if (['unpaid', 'pending', 'dp_paid'].includes(value)) return 'bg-amber-100 text-amber-800';
    return 'bg-rose-100 text-rose-800';
};

const methodLabel = (value: string | null) =>
    ({ cash: 'Tunai', bank_transfer: 'Transfer bank', qris: 'QRIS' }[value || ''] || value || 'Belum ditentukan');

const typeLabel = (value: string | null) =>
    value === 'membership'
        ? 'Membership + Personal Trainer'
        : value === 'trainer_package'
          ? 'Personal Trainer'
          : value === 'booking'
            ? 'Booking Lapangan'
            : value === 'class'
              ? 'Kelas'
              : 'Transaksi';
</script>

<template>
    <Head title="Detail Pembayaran" />

    <div class="w-full space-y-6">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <Link
                    href="/member/payments"
                    class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-orange-700"
                >
                    <ArrowLeft class="size-4" />
                    Kembali ke pembayaran
                </Link>
                <p class="text-xs font-bold uppercase tracking-[.18em] text-orange-700">Riwayat transaksi</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Detail Pembayaran</h1>
                <p class="mt-1 text-sm text-slate-500">Periksa informasi dan status transaksi kamu.</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Nomor invoice</p>
                <p class="mt-1 font-mono text-sm font-bold text-slate-800">{{ payment.invoice_number || 'TRX-' + payment.id }}</p>
            </div>
        </header>

        <section class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(300px,.75fr)]">
            <div class="min-w-0 space-y-6">
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4 bg-[#001428] px-6 py-6 text-white sm:px-8">
                        <div>
                            <p class="text-sm font-medium text-slate-300">Total pembayaran</p>
                            <p class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ currency(payment.amount) }}</p>
                        </div>
                        <span
                            class="inline-flex items-center rounded-full px-3.5 py-2 text-xs font-bold"
                            :class="statusClass(payment.cancelled_at ? 'cancelled' : payment.payment_status)"
                        >
                            {{ payment.cancelled_at ? 'Dibatalkan' : statusLabel(payment.payment_status) }}
                        </span>
                    </div>

                    <div class="grid gap-0 sm:grid-cols-2">
                        <div class="border-b border-slate-100 px-6 py-5 sm:border-r sm:px-8">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Jenis transaksi</p>
                            <p class="mt-2 font-bold text-slate-900">{{ typeLabel(payment.transaction_type) }}</p>
                        </div>
                        <div class="border-b border-slate-100 px-6 py-5 sm:px-8">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Metode pembayaran</p>
                            <p class="mt-2 font-bold text-slate-900">{{ methodLabel(payment.payment_method) }}</p>
                        </div>
                        <div class="px-6 py-5 sm:border-r sm:px-8">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Tanggal transaksi</p>
                            <p class="mt-2 font-bold text-slate-900">{{ dateLabel(payment.created_at) }}</p>
                        </div>
                        <div class="px-6 py-5">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Nama pelanggan</p>
                            <p class="mt-2 font-bold text-slate-900">{{ payment.customer_name || '—' }}</p>
                        </div>
                    </div>
                </article>

                <article v-if="payment.class_details" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5 sm:px-8">
                        <p class="text-xs font-bold uppercase tracking-[.16em] text-lime-700">Informasi pesanan</p>
                        <h2 class="mt-1 text-lg font-bold text-slate-900">Detail kelas</h2>
                    </div>
                    <div class="px-6 py-6 sm:px-8">
                        <h3 class="text-xl font-bold text-slate-900">{{ payment.class_details.name || 'Kelas' }}</h3>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div class="flex items-start gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-lime-50 text-lime-800"><MapPin class="size-5" /></span>
                                <div><p class="text-xs text-slate-400">Lokasi / fasilitas</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ payment.class_details.facility || '—' }}</p></div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-sky-50 text-sky-700"><UserRound class="size-5" /></span>
                                <div><p class="text-xs text-slate-400">Trainer</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ payment.class_details.trainer || 'Belum ditentukan' }}</p></div>
                            </div>
                            <div class="flex items-start gap-3 sm:col-span-2">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-orange-50 text-orange-700"><CalendarDays class="size-5" /></span>
                                <div><p class="text-xs text-slate-400">Jadwal kelas</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ classDateLabel(payment.class_details.date) }}</p><p class="mt-0.5 text-sm text-slate-500">{{ payment.class_details.start_time }}–{{ payment.class_details.end_time }}</p></div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <aside class="min-w-0 space-y-5">
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-orange-50 text-orange-700"><FileText class="size-5" /></span>
                        <div><h2 class="font-bold text-slate-900">Ringkasan transaksi</h2><p class="text-xs text-slate-400">Informasi pembayaran</p></div>
                    </div>
                    <div class="mt-5 space-y-4">
                        <div class="flex items-center justify-between gap-3 text-sm"><span class="text-slate-500">Invoice</span><span class="text-right font-semibold text-slate-800">{{ payment.invoice_number || 'TRX-' + payment.id }}</span></div>
                        <div class="flex items-center justify-between gap-3 text-sm"><span class="text-slate-500">ID transaksi</span><span class="font-semibold text-slate-800">#{{ payment.id }}</span></div>
                        <div v-if="payment.booking_status" class="flex items-center justify-between gap-3 text-sm"><span class="text-slate-500">Status pesanan</span><span class="font-semibold text-slate-800">{{ statusLabel(payment.booking_status) }}</span></div>
                        <div class="border-t border-dashed border-slate-200 pt-4">
                            <p class="text-xs text-slate-500">Total tagihan</p>
                            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ currency(payment.amount) }}</p>
                        </div>
                    </div>
                </article>

                <article v-if="payment.can_cancel" class="rounded-2xl border border-rose-200 bg-rose-50/60 p-6">
                    <div class="flex items-start gap-3">
                        <XCircle class="mt-0.5 size-5 shrink-0 text-rose-600" />
                        <div>
                            <h2 class="font-bold text-rose-900">Batalkan transaksi</h2>
                            <p class="mt-1 text-sm leading-6 text-rose-800/80">Jika tidak ingin melanjutkan, transaksi dan pendaftaran terkait dapat dibatalkan.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="cancelPayment"
                        class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-rose-200 bg-white px-4 py-3 text-sm font-bold text-rose-700 transition hover:bg-rose-100"
                    >
                        Batalkan pembayaran
                    </button>
                </article>
            </aside>
        </section>
    </div>
</template>
