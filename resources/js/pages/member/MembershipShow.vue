<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';

defineOptions({ layout: MemberDashboardLayout });

type Membership = {
  id: number;
  package: string;
  package_type: string | null;
  facility: string | null;
  start_date: string | null;
  end_date: string | null;
  duration_days: number | null;
  duration_minutes: number | null;
  status: string;
  registered_at: string | null;
};

defineProps<{ membership: Membership }>();

const dateLabel = (value: string | null) =>
  value ? new Date(value + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : '—';

const dateTimeLabel = (value: string | null) =>
  value ? new Date(value).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) : '—';

const statusLabel = (value: string) =>
  ({ active: 'Aktif', scheduled: 'Menunggu giliran', expired: 'Kedaluwarsa', cancelled: 'Dibatalkan' }[value] || value);

const typeLabel = (value: string | null) =>
  ({ daily: 'Harian', monthly: 'Bulanan', yearly: 'Tahunan', session: 'Per sesi' }[value || ''] || value || 'Membership');
</script>

<template>
  <Head title="Detail Membership" />
  <div class="space-y-6">
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="text-xs font-bold uppercase tracking-[.18em] text-violet-700">Keanggotaan</p>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Detail Membership</h1>
        <p class="mt-1 text-sm text-slate-500">Informasi lengkap paket dan masa berlaku membership milikmu.</p>
      </div>
      <Link href="/member/membership" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700">
        <span aria-hidden="true">←</span> Kembali
      </Link>
    </header>

    <section class="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="flex flex-wrap items-start justify-between gap-4 bg-[#001428] px-6 py-7 text-white">
        <div>
          <p class="text-xs font-bold uppercase tracking-wider text-violet-200">{{ membership.facility || 'Sport Center' }}</p>
          <h2 class="mt-2 text-2xl font-bold">{{ membership.package }}</h2>
          <p class="mt-2 text-sm text-slate-300">Membership #{{ membership.id }}</p>
        </div>
        <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-bold"
          :class="{
            'bg-emerald-100 text-emerald-800': membership.status === 'active',
            'bg-sky-100 text-sky-800': membership.status === 'scheduled',
            'bg-amber-100 text-amber-800': membership.status === 'expired',
            'bg-rose-100 text-rose-800': membership.status === 'cancelled',
          }">
          {{ statusLabel(membership.status) }}
        </span>
      </div>

      <div class="grid gap-4 p-6 sm:grid-cols-2">
        <div class="rounded-xl bg-slate-50 p-4">
          <p class="text-xs text-slate-500">Nama paket</p>
          <p class="mt-2 text-sm font-semibold text-slate-900">{{ membership.package }}</p>
        </div>
        <div class="rounded-xl bg-slate-50 p-4">
          <p class="text-xs text-slate-500">Jenis paket</p>
          <p class="mt-2 text-sm font-semibold text-slate-900">{{ typeLabel(membership.package_type) }}</p>
        </div>
        <div class="rounded-xl bg-slate-50 p-4">
          <p class="text-xs text-slate-500">Fasilitas</p>
          <p class="mt-2 text-sm font-semibold text-slate-900">{{ membership.facility || 'Sport Center' }}</p>
        </div>
        <div class="rounded-xl bg-slate-50 p-4">
          <p class="text-xs text-slate-500">Durasi paket</p>
          <p class="mt-2 text-sm font-semibold text-slate-900">
            {{ membership.duration_days ? membership.duration_days + ' hari' : membership.duration_minutes ? membership.duration_minutes + ' menit' : '—' }}
          </p>
        </div>
        <div class="rounded-xl bg-slate-50 p-4">
          <p class="text-xs text-slate-500">Tanggal mulai</p>
          <p class="mt-2 text-sm font-semibold text-slate-900">{{ dateLabel(membership.start_date) }}</p>
        </div>
        <div class="rounded-xl bg-slate-50 p-4">
          <p class="text-xs text-slate-500">Tanggal berakhir</p>
          <p class="mt-2 text-sm font-semibold text-slate-900">{{ dateLabel(membership.end_date) }}</p>
        </div>
        <div class="rounded-xl bg-slate-50 p-4 sm:col-span-2">
          <p class="text-xs text-slate-500">Tanggal terdaftar</p>
          <p class="mt-2 text-sm font-semibold text-slate-900">{{ dateTimeLabel(membership.registered_at) }}</p>
        </div>
      </div>
    </section>
  </div>
</template>
