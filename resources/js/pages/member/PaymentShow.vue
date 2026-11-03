<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';

defineOptions({ layout: MemberDashboardLayout });

type Payment = {
  id: number;
  invoice_number: string | null;
  customer_name: string | null;
  customer_type: string | null;
  amount: number;
  payment_method: string | null;
  payment_status: string;
  booking_status: string | null;
  created_at: string | null;
};

defineProps<{ payment: Payment }>();

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
  value === 'member' ? 'Membership' : value || 'Transaksi umum';
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
          <span class="text-sm font-semibold text-slate-900">{{ typeLabel(payment.customer_type) }}</span>
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
    </section>
  </div>
</template>
