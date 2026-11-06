<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';

defineOptions({ layout: MemberDashboardLayout });

type Payment = {
  id: number;
  invoice_number: string | null;
  customer_name: string | null;
  transaction_type: string | null;
  amount: number;
  payment_method: string | null;
  payment_status: string;
  booking_status: string | null;
  can_cancel: boolean;
  created_at: string | null;
};

const props = defineProps<{ payment: Payment }>();

const cancelPayment = () => {
  if (!window.confirm('Yakin ingin membatalkan pembayaran ini? Paket terkait juga akan dibatalkan.')) return;
  router.post('/member/payments/' + props.payment.id + '/cancel');
};

const currency = (value: number) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);

const dateLabel = (value: string | null) =>
  value ? new Date(value).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) : '—';

const statusLabel = (value: string) =>
  ({
    fully_paid: 'Lunas', paid: 'Lunas', unpaid: 'Belum dibayar',
    pending: 'Menunggu pembayaran', dp_paid: 'DP dibayar',
    cancelled: 'Dibatalkan', failed: 'Gagal', expired: 'Kedaluwarsa',
  }[value] || value);

const methodLabel = (value: string | null) =>
  ({ cash: 'Tunai', bank_transfer: 'Transfer bank', qris: 'QRIS' }[value || ''] || value || 'Belum ditentukan');

const typeLabel = (value: string | null) =>
  value === 'membership' ? 'Membership + Personal Trainer' : value === 'trainer_package' ? 'Personal Trainer' : value === 'booking' ? 'Booking' : 'Transaksi';
</script>

<template>
  <Head title="Detail Pembayaran" />
  <div class="mx-auto max-w-3xl space-y-6">
    <header class="space-y-3">
      <Link href="/member/payments" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-orange-700">
        <span aria-hidden="true">←</span> Kembali ke pembayaran
      </Link>
      <div>
        <p class="text-xs font-bold uppercase tracking-[.18em] text-orange-700">Rincian transaksi</p>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Detail Pembayaran</h1>
        <p class="mt-1 text-sm text-slate-500">Informasi lengkap transaksi yang terhubung dengan akunmu.</p>
      </div>
    </header>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
      <div class="bg-[#001428] px-6 py-7 text-white">
        <p class="text-sm text-slate-300">Total pembayaran</p>
        <p class="mt-2 text-3xl font-bold">{{ currency(payment.amount) }}</p>
        <span class="mt-4 inline-flex rounded-full px-3 py-1.5 text-xs font-bold"
          :class="{
            'bg-emerald-100 text-emerald-800': ['fully_paid', 'paid'].includes(payment.payment_status),
            'bg-amber-100 text-amber-800': ['unpaid', 'pending', 'dp_paid'].includes(payment.payment_status),
            'bg-rose-100 text-rose-800': ['failed', 'expired', 'cancelled'].includes(payment.payment_status),
          }">
          {{ statusLabel(payment.payment_status) }}
        </span>
      </div>

      <div class="divide-y divide-slate-100 px-6">
        <div class="flex flex-wrap justify-between gap-2 py-4">
          <span class="text-sm text-slate-500">Nomor invoice</span>
          <span class="text-sm font-semibold text-slate-900">{{ payment.invoice_number || ('TRX-' + payment.id) }}</span>
        </div>
        <div class="flex flex-wrap justify-between gap-2 py-4">
          <span class="text-sm text-slate-500">Jenis transaksi</span>
          <span class="text-sm font-semibold text-slate-900">{{ typeLabel(payment.transaction_type) }}</span>
        </div>
        <div class="flex flex-wrap justify-between gap-2 py-4">
          <span class="text-sm text-slate-500">Nama pelanggan</span>
          <span class="text-sm font-semibold text-slate-900">{{ payment.customer_name || '—' }}</span>
        </div>
        <div class="flex flex-wrap justify-between gap-2 py-4">
          <span class="text-sm text-slate-500">Metode pembayaran</span>
          <span class="text-sm font-semibold text-slate-900">{{ methodLabel(payment.payment_method) }}</span>
        </div>
        <div class="flex flex-wrap justify-between gap-2 py-4">
          <span class="text-sm text-slate-500">Tanggal transaksi</span>
          <span class="text-sm font-semibold text-slate-900">{{ dateLabel(payment.created_at) }}</span>
        </div>
        <div v-if="payment.booking_status" class="flex flex-wrap justify-between gap-2 py-4">
          <span class="text-sm text-slate-500">Status booking</span>
          <span class="text-sm font-semibold text-slate-900">{{ statusLabel(payment.booking_status) }}</span>
        </div>
      </div>

      <div v-if="payment.can_cancel" class="border-t border-slate-100 bg-slate-50 px-6 py-5">
        <p class="mb-3 text-sm text-slate-500">Belum ingin melanjutkan transaksi ini? Kamu bisa membatalkannya.</p>
        <button type="button" @click="cancelPayment" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-white px-5 py-3 text-sm font-bold text-rose-700 transition hover:bg-rose-50">
          Batalkan Pembayaran
        </button>
      </div>
    </section>
  </div>
</template>
