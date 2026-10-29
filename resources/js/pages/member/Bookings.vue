<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';

defineOptions({ layout: MemberDashboardLayout });
type Booking = { id: number; date: string | null; start_time: string | null; end_time: string | null; court: string | null; facility: string | null; status: string };
defineProps<{ bookings: { data: Booking[]; links: { url: string | null; label: string; active: boolean }[] } }>();
const dateLabel = (value: string | null) => value ? new Date(value + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : '—';
const statusLabel = (value: string) => ({ pending: 'Menunggu konfirmasi', confirmed: 'Dikonfirmasi', completed: 'Selesai', cancelled: 'Dibatalkan' }[value] || value);
</script>
<template>
  <Head title="Booking Saya" />
  <div class="space-y-6">
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div><p class="text-xs font-bold uppercase tracking-[.18em] text-lime-700">Aktivitas olahraga</p><h1 class="mt-2 text-2xl font-bold text-slate-900">Booking Saya</h1><p class="mt-1 text-sm text-slate-500">Semua booking yang tersimpan di akunmu.</p></div>
      <a href="/book" class="rounded-xl bg-[#a4da01] px-4 py-3 text-sm font-bold text-[#001428] hover:bg-[#b8ec24]">+ Buat booking</a>
    </header>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
      <div v-if="bookings.data.length" class="divide-y divide-slate-100">
        <article v-for="item in bookings.data" :key="item.id" class="flex flex-wrap items-center justify-between gap-4 p-5">
          <div class="flex items-start gap-4"><div class="grid size-11 shrink-0 place-items-center rounded-xl bg-lime-50 text-lime-800"><span class="text-lg">⚽</span></div><div><p class="font-semibold text-slate-900">{{ item.facility || 'Olahraga' }} <span v-if="item.court" class="font-normal text-slate-500">· {{ item.court }}</span></p><p class="mt-1 text-sm text-slate-500">{{ dateLabel(item.date) }}<span v-if="item.start_time"> · {{ item.start_time }}–{{ item.end_time }}</span></p><p class="mt-1 text-xs text-slate-400">Kode booking #{{ item.id }}</p></div></div>
          <span class="rounded-full px-3 py-1.5 text-xs font-bold" :class="{'bg-amber-50 text-amber-700':item.status==='pending','bg-sky-50 text-sky-700':item.status==='confirmed','bg-emerald-50 text-emerald-700':item.status==='completed','bg-rose-50 text-rose-700':item.status==='cancelled'}">{{ statusLabel(item.status) }}</span>
        </article>
      </div>
      <div v-else class="px-6 py-16 text-center"><div class="mx-auto grid size-14 place-items-center rounded-2xl bg-slate-50 text-2xl">📅</div><h2 class="mt-4 font-bold text-slate-900">Belum ada booking</h2><p class="mt-1 text-sm text-slate-500">Booking lapangan pertama kamu akan muncul di sini.</p><a href="/book" class="mt-5 inline-flex rounded-xl bg-[#001428] px-4 py-3 text-sm font-semibold text-white">Cari lapangan</a></div>
    </div>
    <nav v-if="bookings.links.length > 3" class="flex flex-wrap gap-2"><template v-for="link in bookings.links" :key="link.label"><Link v-if="link.url" :href="link.url" class="rounded-lg border px-3 py-2 text-sm" :class="link.active?'border-[#a4da01] bg-lime-50 font-bold':'border-slate-200 bg-white text-slate-600'" v-html="link.label" /><span v-else class="rounded-lg border border-slate-100 px-3 py-2 text-sm text-slate-300" v-html="link.label" /></template></nav>
  </div>
</template>