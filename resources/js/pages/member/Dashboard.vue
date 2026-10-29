<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight, CalendarDays, Clock3, CreditCard, Plus, Trophy } from '@lucide/vue';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';

defineOptions({ layout: MemberDashboardLayout });
type Booking = { id:number; date:string|null; start_time:string|null; end_time:string|null; court:string|null; facility:string|null; status:string };
type Membership = { id:number; package:string; facility:string|null; start_date:string|null; end_date:string|null; duration_days:number|null; status:string };
defineProps<{
  stats:{upcoming_bookings:number;completed_bookings:number;active_memberships:number;payments_count:number;payments_total:number};
  bookings:Booking[];
  memberships:Membership[];
}>();
const currency=(value:number)=>new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(value);
const dateLabel=(value:string|null)=>value?new Date(value+'T00:00:00').toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'}):'Jadwal belum ditentukan';
const statusLabel=(value:string)=>({pending:'Menunggu konfirmasi',confirmed:'Dikonfirmasi',completed:'Selesai',cancelled:'Dibatalkan',active:'Aktif',expired:'Kedaluwarsa'}[value]||value);
const summaries = (stats:{upcoming_bookings:number;completed_bookings:number;active_memberships:number;payments_count:number;payments_total:number}) => [
 {label:'Booking mendatang',value:String(stats.upcoming_bookings),note:'Jadwal yang sudah kamu pesan',icon:CalendarDays,tone:'lime'},
 {label:'Booking selesai',value:String(stats.completed_bookings),note:'Total sesi olahraga selesai',icon:Clock3,tone:'blue'},
 {label:'Membership aktif',value:String(stats.active_memberships),note:'Paket keanggotaan kamu',icon:Trophy,tone:'violet'},
 {label:'Total pembayaran lunas',value:currency(stats.payments_total),note:stats.payments_count+' transaksi tercatat',icon:CreditCard,tone:'orange'},
];
</script>
<template>
  <Head title="Dashboard Member" />
  <div class="space-y-7">
    <section class="relative isolate overflow-hidden rounded-3xl bg-[#001428] p-6 text-white sm:p-8 lg:p-10">
      <div class="absolute -right-10 -top-20 -z-10 size-72 rounded-full bg-[#a4da01]/20 blur-3xl" /><div class="absolute bottom-0 right-1/4 -z-10 size-40 rounded-full bg-sky-400/10 blur-3xl" />
      <div class="max-w-2xl"><p class="text-xs font-bold uppercase tracking-[.2em] text-[#b9ec35]">Member dashboard</p><h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Halo, {{ $page.props.auth?.user?.name || 'Member' }}! 👋</h1><p class="mt-3 max-w-xl text-sm leading-6 text-slate-300 sm:text-base">Semua booking, membership, dan transaksi yang terhubung dengan akunmu bisa dipantau di sini.</p><a href="/book" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#a4da01] px-5 py-3 text-sm font-bold text-[#001428] transition hover:-translate-y-0.5 hover:bg-[#b8ec24]"><Plus class="size-4" /> Buat booking <ArrowUpRight class="size-4" /></a></div>
      <div class="absolute bottom-0 right-7 hidden opacity-20 md:block"><Trophy class="size-40" :stroke-width="1" /></div>
    </section>
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article v-for="item in summaries(stats)" :key="item.label" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_4px_20px_rgba(15,23,42,.03)] transition duration-200 hover:-translate-y-1 hover:shadow-lg"><div class="flex items-start justify-between gap-3"><div><p class="text-sm font-medium text-slate-500">{{ item.label }}</p><p class="mt-3 text-2xl font-bold tracking-tight text-slate-900">{{ item.value }}</p></div><div class="grid size-11 place-items-center rounded-xl" :class="{'bg-lime-100 text-lime-800':item.tone==='lime','bg-sky-100 text-sky-700':item.tone==='blue','bg-violet-100 text-violet-700':item.tone==='violet','bg-orange-100 text-orange-700':item.tone==='orange'}"><component :is="item.icon" class="size-5" /></div></div><p class="mt-4 text-xs text-slate-400">{{ item.note }}</p></article>
    </section>
    <section class="grid gap-5 xl:grid-cols-[1.35fr_1fr]">
      <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex items-center justify-between gap-3"><div><h2 class="font-bold text-slate-900">Booking terbaru</h2><p class="mt-1 text-sm text-slate-500">Data booking dari akunmu</p></div><Link href="/member/bookings" class="text-sm font-semibold text-lime-800 hover:underline">Lihat semua</Link></div>
        <div v-if="bookings.length" class="mt-5 divide-y divide-slate-100"><div v-for="item in bookings" :key="item.id" class="flex items-center justify-between gap-3 py-4"><div><p class="text-sm font-semibold text-slate-900">{{ item.facility || 'Olahraga' }}<span v-if="item.court" class="font-normal text-slate-500"> · {{ item.court }}</span></p><p class="mt-1 text-xs text-slate-500">{{ dateLabel(item.date) }}<span v-if="item.start_time"> · {{ item.start_time }}–{{ item.end_time }}</span></p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ statusLabel(item.status) }}</span></div></div>
        <div v-else class="mt-5 rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 px-5 py-9 text-center"><CalendarDays class="mx-auto size-8 text-slate-400" /><h3 class="mt-3 text-sm font-semibold text-slate-800">Belum ada booking</h3><p class="mt-1 text-sm text-slate-500">Booking lapangan dan datanya akan tersimpan di sini.</p><a href="/book" class="mt-4 inline-flex rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-200">Cari jadwal</a></div>
      </div>
      <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex items-center justify-between gap-3"><div><h2 class="font-bold text-slate-900">Membership saya</h2><p class="mt-1 text-sm text-slate-500">Paket yang terdaftar pada akunmu</p></div><Link href="/member/membership" class="text-sm font-semibold text-violet-700 hover:underline">Lihat semua</Link></div>
        <div v-if="memberships.length" class="mt-5 space-y-3"><article v-for="item in memberships.slice(0,3)" :key="item.id" class="rounded-xl border border-slate-100 p-4"><div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-slate-900">{{ item.package }}</p><p class="mt-1 text-xs text-slate-500">{{ item.facility || 'Sport Center' }}</p></div><span class="rounded-full bg-lime-50 px-2.5 py-1 text-xs font-bold text-lime-800">{{ statusLabel(item.status) }}</span></div><p class="mt-3 text-xs text-slate-500">Berlaku {{ dateLabel(item.start_date) }} – {{ dateLabel(item.end_date) }}</p></article></div>
        <div v-else class="mt-5 rounded-2xl bg-gradient-to-br from-[#f5fbdc] to-[#f8fafc] p-5"><span class="inline-flex rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-600">Belum berlangganan</span><h3 class="mt-4 text-lg font-bold text-slate-900">Mulai jadi member</h3><p class="mt-2 text-sm leading-6 text-slate-600">Pilih package membership yang tersedia dan pantau statusnya di sini.</p><a href="/membership" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#001428] px-4 py-3 text-sm font-semibold text-white">Lihat package <ArrowUpRight class="size-4" /></a></div>
      </div>
    </section>
  </div>
</template>