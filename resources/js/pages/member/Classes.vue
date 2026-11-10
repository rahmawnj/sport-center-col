<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import MemberDashboardLayout from '@/layouts/member/MemberDashboardLayout.vue';
defineOptions({layout:MemberDashboardLayout});
type Schedule={id:number;session_date:string;class_name:string;description:string|null;facility:string|null;trainer:string|null;trainer_specialty:string|null;day:string;start_time:string;end_time:string;capacity:number;registered_count:number;available_count:number;price:number};
defineProps<{schedules:Schedule[]}>();
const busy=ref<number|null>(null);
const money=(v:number)=>new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(v);
const dateLabel=(v:string)=>new Date(v+'T00:00:00').toLocaleDateString('id-ID',{weekday:'long',day:'2-digit',month:'long',year:'numeric'});
function register(s:Schedule,payment_method:'cash'|'bank_transfer'){if(busy.value!==null)return;busy.value=s.id;router.post('/member/classes/'+s.id+'/register',{payment_method,session_date:s.session_date},{onFinish:()=>busy.value=null});}
</script>
<template>
<Head title="Kelas & Jadwal"/>
<div class="space-y-6">
<header><p class="text-xs font-bold uppercase tracking-[.18em] text-lime-700">Aktivitas olahraga</p><h1 class="mt-2 text-2xl font-bold text-slate-900">Kelas & Jadwal</h1><p class="mt-1 text-sm text-slate-500">Temukan kelas yang tersedia dan amankan tempatmu.</p></header>
<div v-if="schedules.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
<article v-for="s in schedules" :key="s.id + '-' + s.session_date" class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
<div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wider text-lime-700">{{s.facility||'Sport Center'}}</p><h2 class="mt-1 text-xl font-bold text-slate-900">{{s.class_name}}</h2></div><span class="rounded-full bg-lime-50 px-3 py-1 text-xs font-bold text-lime-800">{{s.available_count}} slot</span></div>
<p v-if="s.description" class="mt-3 line-clamp-2 text-sm text-slate-500">{{s.description}}</p>
<div class="mt-4 space-y-2 text-sm text-slate-600"><p>📅 {{dateLabel(s.session_date)}}</p><p>🕒 {{s.start_time}}–{{s.end_time}}</p><p>🏋️ {{s.trainer||'Trainer akan diumumkan'}}</p><p>👥 {{s.registered_count}} dari {{s.capacity}} peserta</p></div>
<div class="mt-auto border-t border-slate-100 pt-4"><p class="text-lg font-extrabold text-slate-900">{{money(s.price)}}</p><p class="mb-3 text-xs text-slate-400">Harga per peserta</p><div class="grid grid-cols-2 gap-2"><button type="button" :disabled="s.available_count<1||busy!==null" class="rounded-xl bg-[#a4da01] px-3 py-3 text-xs font-bold text-[#001428] disabled:cursor-not-allowed disabled:opacity-50" @click="register(s,'bank_transfer')">Daftar · Transfer</button><button type="button" :disabled="s.available_count<1||busy!==null" class="rounded-xl border border-slate-200 px-3 py-3 text-xs font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50" @click="register(s,'cash')">Daftar · Tunai</button></div></div>
</article>
</div>
<div v-else class="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center"><div class="text-3xl">🧘</div><h2 class="mt-3 font-bold text-slate-900">Belum ada jadwal kelas</h2><p class="mt-1 text-sm text-slate-500">Coba cek lagi nanti, jadwal kelas baru akan muncul di sini.</p></div>
</div>
</template>