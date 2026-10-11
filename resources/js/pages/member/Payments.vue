<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';

defineOptions({ layout: MemberDashboardLayout });
type Payment={id:number;invoice_number:string|null;reference:string;amount:number;payment_method:string|null;payment_status:string;created_at:string|null};
defineProps<{payments:{data:Payment[];links:{url:string|null;label:string;active:boolean}[]} }>();
const currency=(value:number)=>new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(value);
const dateLabel=(value:string|null)=>value?new Date(value).toLocaleString('id-ID',{dateStyle:'medium',timeStyle:'short'}):'—';
const statusLabel=(value:string)=>({paid:'Lunas',pending:'Menunggu pembayaran',unpaid:'Belum dibayar',dp_paid:'DP dibayar',fully_paid:'Lunas'}[value]||value);
</script>
<template>
  <Head title="Pembayaran Saya" />
  <div class="space-y-6">
    <header><p class="text-xs font-bold uppercase tracking-[.18em] text-orange-700">Riwayat transaksi</p><h1 class="mt-2 text-2xl font-bold text-slate-900">Pembayaran Saya</h1><p class="mt-1 text-sm text-slate-500">Riwayat transaksi booking dan membership yang terhubung ke akunmu.</p></header>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
      <div v-if="payments.data.length" class="divide-y divide-slate-100">
        <article v-for="item in payments.data" :key="item.id" class="flex flex-wrap items-center justify-between gap-4 p-5"><div><p class="font-semibold text-slate-900">{{ item.reference }} <span class="text-sm font-normal text-slate-400">{{ item.invoice_number || '#'+item.id }}</span></p><p class="mt-1 text-sm text-slate-500">{{ dateLabel(item.created_at) }} · {{ item.payment_method || 'Metode belum ditentukan' }}</p></div><div class="text-left sm:text-right"><p class="font-bold text-slate-900">{{ currency(item.amount) }}</p><span class="mt-1 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ statusLabel(item.payment_status) }}</span></div></article>
      </div>
      <div v-else class="px-6 py-16 text-center"><div class="mx-auto grid size-14 place-items-center rounded-2xl bg-orange-50 text-2xl">💳</div><h2 class="mt-4 font-bold text-slate-900">Belum ada transaksi</h2><p class="mt-1 text-sm text-slate-500">Pembayaran yang terkait dengan akunmu akan muncul di sini.</p><Link href="/membership" class="mt-5 inline-flex rounded-xl bg-[#001428] px-4 py-3 text-sm font-semibold text-white">Lihat paket membership</Link></div>
    </div>
    <nav v-if="payments.links.length > 3" class="flex flex-wrap gap-2"><template v-for="link in payments.links" :key="link.label"><Link v-if="link.url" :href="link.url" class="rounded-lg border px-3 py-2 text-sm" :class="link.active?'border-orange-300 bg-orange-50 font-bold':'border-slate-200 bg-white text-slate-600'" v-html="link.label" /><span v-else class="rounded-lg border border-slate-100 px-3 py-2 text-sm text-slate-300" v-html="link.label" /></template></nav>
  </div>
</template>