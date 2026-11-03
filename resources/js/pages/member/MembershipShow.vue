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
  ({ active: 'Aktif', expired: 'Kedaluwarsa', cancelled: 'Dibatalkan' }[value] || value);

const typeLabel = (value: string | null) =>
  ({ daily: 'Harian', monthly: 'Bulanan', yearly: 'Tahunan', session: 'Per sesi' }[value || ''] || value || 'Membership');
</script>

<template>
  <Head title="Detail Membership" />
  <div class="mx-auto max-w-3xl space-y-6">
    <header class="space-y-3">
      <Link href="/member/membership" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-violet-700">
        <span aria-hidden="true">←</span> Kembali ke membership
      </Link>
      <div>
        <p class="text-xs font-bold uppercase tracking-[.18em] text-violet-700">Rincian keanggotaan</p>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Detail Membership</h1>
        <p class="mt-1 text-sm text-slate-500">Informasi paket dan masa berlaku membership milikmu.</p>
      </div>
    </header>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
      <div class="bg-[#001428] px-6 py-7 text-white">
        <p class="text-sm text-slate-300">{{ membership.facility || 'Sport Center' }}</p>
        <h2 class="mt-2 text-2xl font-bold">{{ membership.package }}</h2>
        <p class="mt-2 text-sm text-slate-300">Membership #{{ membership.id }}</p>
        <span class="mt-4 inline-flex rounded-full px-3 py-1.5 text-xs font-bold"
          :class="{
            'bg-emerald-100 text-emerald-800': membership.status === 'active',
            'bg-amber-100 text-amber-800': membership.status === 'expired',
            'bg-rose-100 text-rose-800': membership.status === 'cancelled',
          }">
          {{ statusLabel(membership.status) }}
        </span>
      </div>

      <div class="divide-y divide-slate-100 px-6">
        <div class="flex flex-wrap justify-between gap-2 py-4">
          <span class="text-sm text-slate-500">Nama paket</span>
          <span class="text-sm font-semibold text-slate-900">{{ membership.package }}</span>
        </div>
        <div class="flex flex-wrap justify-between gap-2 py-4">
          <span class="text-sm text-slate-500">Jenis paket</span>
          <span class="text-sm font-semibold text-slate-900">{{ typeLabel(membership.package_type) }}</span>
        </div>
        <div class="flex flex-wrap justify-between gap-2 py-4">
          <span class="text-sm text-slate-500">Fasilitas</span>
          <span class="text-sm font-semibold text-slate-900">{{ membership.facility || 'Sport Center' }}</span>
        </div>
        <div class="flex flex-wrap justify-between gap-2 py-4">
          <span class="text-sm text-slate-500">Tanggal mulai</span>
          <span class="text-sm font-semibold text-slate-900">{{ dateLabel(membership.start_date) }}</span>
        </div>
        <div class="flex flex-wrap justify-between gap-2 py-4">
          <span class="text-sm text-slate-500">Tanggal berakhir</span>
          <span class="text-sm font-semibold text-slate-900">{{ dateLabel(membership.end_date) }}</span>
        </div>
        <div v-if="membership.duration_days" class="flex flex-wrap justify-between gap-2 py-4">
          <span class="text-sm text-slate-500">Durasi paket</span>
          <span class="text-sm font-semibold text-slate-900">{{ membership.duration_days }} hari</span>
        </div>
        <div v-if="membership.duration_minutes" class="flex flex-wrap justify-between gap-2 py-4">
          <span class="text-sm text-slate-500">Durasi sesi</span>
          <span class="text-sm font-semibold text-slate-900">{{ membership.duration_minutes }} menit</span>
        </div>
        <div class="flex flex-wrap justify-between gap-2 py-4">
          <span class="text-sm text-slate-500">Tanggal terdaftar</span>
          <span class="text-sm font-semibold text-slate-900">{{ dateTimeLabel(membership.registered_at) }}</span>
        </div>
      </div>
    </section>
  </div>
</template>
