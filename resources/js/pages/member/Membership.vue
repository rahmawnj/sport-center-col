<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';

defineOptions({ layout: MemberDashboardLayout });
type Membership = { id:number; package:string; facility:string|null; start_date:string|null; end_date:string|null; duration_days:number|null; status:string };
defineProps<{ memberships:{data:Membership[];links:{url:string|null;label:string;active:boolean}[]} }>();
const dateLabel = (value:string|null) => value ? new Date(value+'T00:00:00').toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}) : '—';
const statusLabel = (value:string) => ({active:'Aktif',expired:'Kedaluwarsa',cancelled:'Dibatalkan'}[value] || value);
</script>
<template>
  <Head title="Membership Saya" />
  <div class="space-y-6">
    <header class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[.18em] text-violet-700">Keanggotaan</p><h1 class="mt-2 text-2xl font-bold text-slate-900">Membership Saya</h1><p class="mt-1 text-sm text-slate-500">Pantau paket, masa berlaku, dan status membership milikmu.</p></div><Link href="/membership" class="rounded-xl bg-[#a4da01] px-4 py-3 text-sm font-bold text-[#001428] hover:bg-[#b8ec24]">Lihat paket membership</Link></header>
    <div v-if="memberships.data.length" class="grid gap-4 lg:grid-cols-2">
      <article v-for="item in memberships.data" :key="item.id" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-wider text-violet-700">{{ item.facility || 'Sport Center' }}</p><h2 class="mt-2 text-xl font-bold text-slate-900">{{ item.package }}</h2></div><span class="rounded-full px-3 py-1.5 text-xs font-bold" :class="{'bg-emerald-50 text-emerald-700':item.status==='active','bg-amber-50 text-amber-700':item.status==='expired','bg-rose-50 text-rose-700':item.status==='cancelled'}">{{ statusLabel(item.status) }}</span></div>
        <div class="mt-6 grid grid-cols-2 gap-4"><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Tanggal mulai</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ dateLabel(item.start_date) }}</p></div><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Tanggal berakhir</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ dateLabel(item.end_date) }}</p></div></div>
        <p v-if="item.duration_days" class="mt-4 text-xs text-slate-400">Durasi paket: {{ item.duration_days }} hari · ID membership #{{ item.id }}</p>
      </article>
    </div>
    <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"><div class="mx-auto grid size-14 place-items-center rounded-2xl bg-violet-50 text-2xl">🏆</div><h2 class="mt-4 font-bold text-slate-900">Belum ada membership</h2><p class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">Pilih package membership yang tersedia untuk mulai menjadi member. Paket yang sudah terdaftar akan muncul di halaman ini.</p><Link href="/membership" class="mt-5 inline-flex rounded-xl bg-[#001428] px-4 py-3 text-sm font-semibold text-white">Jelajahi package</Link></div>
    <nav v-if="memberships.links.length > 3" class="flex flex-wrap gap-2"><template v-for="link in memberships.links" :key="link.label"><Link v-if="link.url" :href="link.url" class="rounded-lg border px-3 py-2 text-sm" :class="link.active?'border-violet-300 bg-violet-50 font-bold':'border-slate-200 bg-white text-slate-600'" v-html="link.label" /><span v-else class="rounded-lg border border-slate-100 px-3 py-2 text-sm text-slate-300" v-html="link.label" /></template></nav>
  </div>
</template>